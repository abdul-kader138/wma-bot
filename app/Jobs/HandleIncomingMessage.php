<?php

namespace App\Jobs;

use App\Contracts\MessagingChannel;
use App\Models\ClaudeDailyUsage;
use App\Models\Conversation;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\WhatsAppAccount;
use App\Notifications\BotJobFailedNotification;
use App\Notifications\ClaudeBudgetExhaustedNotification;
use App\Notifications\NewServiceRequestNotification;
use App\Services\ClaudeAgent;
use App\Services\FaqMatcher;
use App\Services\InstagramClient;
use App\Services\Maria\AssistantIdentityResolver;
use App\Services\Maria\MariaConversationService;
use App\Services\MessengerClient;
use App\Services\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

class HandleIncomingMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Shared across every WhatsApp account and customer: there's one Anthropic API
    // key (Setting::get('claude_api_key')) for the whole app, so the budget protecting
    // it has to be shared too, not per-conversation.
    private const CLAUDE_RATE_LIMIT_KEY = 'claude-api-calls';

    public int $tries = 3;

    // Headroom for: lock wait (up to 10s) + Claude call (up to 40s) + WhatsApp send (up to 10s) + overhead.
    public int $timeout = 90;

    // Defaults to 'whatsapp' so every existing dispatch call site (just the WhatsApp
    // webhook controller today) keeps working unchanged — Messenger/Instagram webhook
    // controllers pass 'messenger'/'instagram' explicitly.
    public function __construct(public array $value, public int $whatsAppAccountId, public string $platform = 'whatsapp') {}

    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function handle(
        ClaudeAgent $agent,
        FaqMatcher $faqs,
        ?AssistantIdentityResolver $assistantIdentities = null,
        ?MariaConversationService $maria = null,
    ): void {
        $assistantIdentities ??= app(AssistantIdentityResolver::class);
        $maria ??= app(MariaConversationService::class);

        $account = WhatsAppAccount::findOrFail($this->whatsAppAccountId);

        if (! $account->is_active) {
            Log::warning('Skipping message for deactivated channel account', [
                'account_id' => $account->id,
                'platform' => $this->platform,
            ]);

            return;
        }

        $wa = $this->resolveChannel($account);

        $msg = $wa->parseIncoming($this->value);
        if (! $msg) {
            return;
        }

        // Deduplicate: skip if we've processed this message ID already. Only on the
        // first attempt — $this->release() (lock contention, rate limiting) re-queues
        // this same job and bumps attempts(), and that retry must go through: the key
        // was already cached by this job's own first attempt, so without this guard
        // every released retry would see it as "already handled" and silently no-op,
        // dropping the message instead of actually retrying it.
        if ($this->attempts() === 1 && $msg['message_id']
            && ! Cache::add("wa_msg:{$msg['message_id']}", 1, now()->addHours(24))) {
            return;
        }

        $phone = $msg['phone'];

        // Serialize processing per customer: with more than one queue worker running,
        // two rapid messages from the same person could otherwise be picked up by two
        // workers at once and race on the same Conversation row (lost updates, or the
        // AI tool call firing twice and creating duplicate ServiceRequest rows).
        //
        // The lock's own TTL (how long it stays valid before auto-expiring as a crash
        // safety net) must be >= the longest this job can legitimately run — otherwise
        // a slow Claude response could let the lock expire mid-processing and let a
        // second worker in anyway. Match it to the job timeout below.
        $lock = Cache::lock("conversation-lock:{$account->id}:{$phone}", 90);

        try {
            $lock->block(10, function () use ($agent, $faqs, $assistantIdentities, $maria, $account, $phone, $msg, $wa) {
                $this->processMessage($agent, $faqs, $assistantIdentities, $maria, $account, $phone, $msg, $wa);
            });
        } catch (LockTimeoutException) {
            // Another message from this same customer is still being processed right
            // now. Put this one back on the queue briefly rather than racing it.
            $this->release(3);
        }
    }

    private function resolveChannel(WhatsAppAccount $account): MessagingChannel
    {
        return match ($this->platform) {
            'messenger' => new MessengerClient($account),
            'instagram' => new InstagramClient($account),
            default => new WhatsAppClient($account),
        };
    }

    private function processMessage(
        ClaudeAgent $agent,
        FaqMatcher $faqs,
        AssistantIdentityResolver $assistantIdentities,
        MariaConversationService $maria,
        WhatsAppAccount $account,
        string $phone,
        array $msg,
        MessagingChannel $wa,
    ): void {
        $input = $msg['reply_id'] ?? trim((string) ($msg['text'] ?? ''));

        // Maria is structurally private: only an active, verified identity tied to
        // this exact channel account can enter the assistant path. Everyone else
        // continues through the existing public intake state machine unchanged.
        $identity = $assistantIdentities->resolveIdentity($this->platform, $phone, $account->id);
        if ($identity) {
            // Maria shares the single Anthropic API key/account rate limit with the public
            // bot below, so an unmetered Maria path could exhaust that shared budget and
            // start failing customer conversations. Gate it through the same per-minute
            // limiter (not the customer-abuse daily/session caps, which don't apply to an
            // allowlisted staff identity).
            if ($this->deferIfClaudeRateLimited()) {
                return;
            }

            $wa->sendText($phone, $maria->handle($identity, $input));

            return;
        }

        $convo = Conversation::firstOrCreate(
            ['wa_phone' => $phone, 'whatsapp_account_id' => $account->id],
            ['step' => 'NEW', 'history' => [], 'platform' => $this->platform]
        );

        // Reset keywords, covering all supported languages (en/it/bn) so any customer
        // can restart the conversation in their own language.
        if (in_array(mb_strtolower($input), ['menu', 'start', 'restart', 'hi', 'hello', 'ciao', 'হ্যালো', 'শুরু'])) {
            $convo->update(['step' => 'NEW', 'service' => null, 'history' => [], 'claude_message_count' => 0]);
        }

        switch ($convo->step) {
            case 'NEW':
                $wa->sendLanguageList($phone);
                $convo->update(['step' => 'AWAIT_LANG']);
                break;

            case 'AWAIT_LANG':
                if (! array_key_exists($input, config('services_bot.languages'))) {
                    $wa->sendLanguageList($phone);
                    break;
                }
                $convo->update(['language' => $input, 'step' => 'AWAIT_SERVICE']);
                $wa->sendServiceButtons($phone, $input);
                break;

            case 'AWAIT_SERVICE':
                if (! array_key_exists($input, Service::toConfig($account->id))) {
                    $wa->sendServiceButtons($phone, $convo->language ?? 'en');
                    break;
                }

                // Checked before any mutation: if we're over the shared Claude budget,
                // release the whole job untouched so the retry re-validates this same
                // service pick from scratch, instead of half-committing (welcome sent,
                // step already advanced) and then confusing the retry's replay of $input.
                if ($this->deferIfClaudeRateLimited()) {
                    break;
                }

                $convo->update(['service' => $input, 'step' => 'IN_SERVICE', 'history' => [], 'claude_message_count' => 0]);
                $this->sendWelcome($wa, $convo, $phone);

                // These three gates are all "hard stop" (no queue/retry, just tell the
                // customer and skip the call) unlike deferIfClaudeRateLimited() above —
                // see each method's own comment for why it exists and what it protects.
                if ($this->circuitBreakerTripped($wa, $phone, $convo->language)
                    || $this->dailyLimitReached($account, $phone, $wa, $convo->language)) {
                    break;
                }

                $this->runAgent($wa, $agent, $convo, $phone);
                break;

            case 'IN_SERVICE':
                if ($faq = $faqs->match($input, $convo->service, $account->id)) {
                    $this->appendHistory($convo, 'user', $input);
                    $answer = $faq->answerFor($convo->language ?? 'en');
                    $wa->sendText($phone, $answer);
                    $this->appendHistory($convo, 'assistant', $answer);
                    break;
                }

                // Same reasoning as above: gate before appendHistory() so a released
                // retry doesn't log the customer's message twice (which would also
                // break the Claude call itself — the API rejects back-to-back turns
                // with the same role).
                if ($this->deferIfClaudeRateLimited()) {
                    break;
                }

                // Severity order: global kill switch first (protects everyone), then the
                // per-phone daily budget (survives a "menu" reset, unlike the next one),
                // then the per-session cap (cheapest to hit, resets on "menu").
                if ($this->circuitBreakerTripped($wa, $phone, $convo->language)
                    || $this->dailyLimitReached($account, $phone, $wa, $convo->language)
                    || $this->sessionLimitReached($convo, $wa, $phone)) {
                    break;
                }

                $this->appendHistory($convo, 'user', $input);
                $this->runAgent($wa, $agent, $convo, $phone);
                break;

            case 'DONE':
            default:
                $convo->update(['step' => 'AWAIT_LANG', 'history' => [], 'claude_message_count' => 0]);
                $wa->sendLanguageList($phone);
                break;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('HandleIncomingMessage failed', [
            'error' => $e->getMessage(),
            'value' => $this->value,
        ]);

        if ($email = Setting::get('staff_notification_email')) {
            try {
                Notification::route('mail', $email)
                    ->notify(new BotJobFailedNotification($e->getMessage(), $this->value));
            } catch (\Throwable $notifyError) {
                Log::error('Failed to send bot failure notification', ['error' => $notifyError->getMessage()]);
            }
        }
    }

    // Shared across every account/customer, since there's a single Claude API key.
    // Returns true (and releases the job for a later retry) if we're over budget;
    // the caller must bail out immediately without having mutated anything yet.
    private function deferIfClaudeRateLimited(): bool
    {
        $maxPerMinute = max(1, (int) Setting::get('claude_rate_limit_per_minute', 50));

        if (RateLimiter::tooManyAttempts(self::CLAUDE_RATE_LIMIT_KEY, $maxPerMinute)) {
            $this->release(RateLimiter::availableIn(self::CLAUDE_RATE_LIMIT_KEY));

            return true;
        }

        RateLimiter::hit(self::CLAUDE_RATE_LIMIT_KEY, 60);

        return false;
    }

    // Per-conversation ceiling on paid Claude calls, distinct from deferIfClaudeRateLimited()'s
    // shared requests-per-minute budget: this caps how many times a single customer can make
    // the bot call the API within one session, so one chatty conversation can't run up an
    // unbounded token bill. Resets whenever the conversation's history resets (new service,
    // restart keyword, or session end) — see the claude_message_count => 0 updates above.
    //
    // Deliberately the weakest of the three caps: a customer can clear it any time by typing
    // "menu". circuitBreakerTripped() and dailyLimitReached() below are what actually stop abuse.
    private function sessionLimitReached(Conversation $convo, MessagingChannel $wa, string $phone): bool
    {
        $max = max(1, (int) Setting::get('claude_max_messages_per_session', 20));

        if ($convo->claude_message_count < $max) {
            return false;
        }

        if ($message = $this->localizedMessage('claude_session_limit_message', $convo->language)) {
            $wa->sendText($phone, $message);
        }

        $convo->update(['step' => 'DONE', 'history' => [], 'claude_message_count' => 0]);

        return true;
    }

    // Global, across every account and customer: an absolute daily ceiling on Claude calls,
    // independent of the per-minute burst limiter. This is the "something is badly wrong,
    // stop spending" circuit breaker — 0 disables it, since a sensible cap depends entirely
    // on the owner's actual traffic and Anthropic budget. Stays tripped until the day rolls
    // over; staff are emailed once per day it trips, not once per blocked message.
    //
    // Reads claude_daily_usages rather than a cache counter — that table is the same one
    // dailyLimitReached() below books usage into, so the global total can never drift from
    // the sum of what customers actually used, and it works identically on every cache
    // driver (production runs Redis, which has no cheap way to enumerate cache keys —
    // see ClaudeUsageTracker).
    private function circuitBreakerTripped(MessagingChannel $wa, string $phone, ?string $lang): bool
    {
        $cap = (int) Setting::get('claude_daily_global_cap', 0);

        if ($cap <= 0) {
            return false;
        }

        $total = (int) ClaudeDailyUsage::whereDate('date', today())->sum('count');

        if ($total < $cap) {
            return false;
        }

        if (Cache::add('claude-breaker-notified:'.now()->toDateString(), true, now()->endOfDay())) {
            $this->notifyStaffBudgetExhausted($cap);
        }

        if ($message = $this->localizedMessage('bot_fallback_message', $lang)) {
            $wa->sendText($phone, $message);
        }

        return true;
    }

    // Per phone number, spanning every session — this is what actually stops abuse that
    // sessionLimitReached() can't: a customer can reset the per-session counter any time by
    // typing "menu", but this row is keyed to the phone number, not the conversation row, so
    // it survives that.
    //
    // Also does the bookkeeping increment for a granted call — the single place usage gets
    // recorded, so circuitBreakerTripped()'s global sum above always matches. Runs (and
    // increments) even when claude_max_messages_per_day is 0/disabled, since the global
    // breaker still needs an accurate count regardless of whether the per-phone cap is on.
    private function dailyLimitReached(WhatsAppAccount $account, string $phone, MessagingChannel $wa, ?string $lang): bool
    {
        $usage = ClaudeDailyUsage::firstOrCreate(
            ['whatsapp_account_id' => $account->id, 'phone' => $phone, 'date' => today()->toDateString()],
            ['count' => 0, 'platform' => $this->platform]
        );

        $max = (int) Setting::get('claude_max_messages_per_day', 100);

        if ($max > 0 && $usage->count >= $max) {
            if ($message = $this->localizedMessage('claude_daily_limit_message', $lang)) {
                $wa->sendText($phone, $message);
            }

            return true;
        }

        $usage->increment('count');

        return false;
    }

    private function localizedMessage(string $settingKey, ?string $lang): ?string
    {
        $messages = Setting::get($settingKey, []);

        return $messages[$lang ?? 'en'] ?? $messages['en'] ?? null;
    }

    private function notifyStaffBudgetExhausted(int $cap): void
    {
        $email = Setting::get('staff_notification_email');
        if (! $email) {
            return;
        }

        try {
            Notification::route('mail', $email)
                ->notify(new ClaudeBudgetExhaustedNotification($cap));
        } catch (\Throwable $e) {
            Log::error('Failed to send Claude budget exhausted notification', ['error' => $e->getMessage()]);
        }
    }

    private function notifyStaff(ServiceRequest $serviceRequest): void
    {
        $email = Setting::get('staff_notification_email');
        if (! $email) {
            return;
        }

        try {
            Notification::route('mail', $email)
                ->notify(new NewServiceRequestNotification($serviceRequest));
        } catch (\Throwable $e) {
            Log::error('Failed to send staff notification email', ['error' => $e->getMessage()]);
        }
    }

    private function runAgent(MessagingChannel $wa, ClaudeAgent $agent, Conversation $convo, string $phone): void
    {
        // The service the customer picked may have been deactivated or deleted since
        // (Service::toConfig() only returns active ones) — recover instead of letting
        // ClaudeAgent crash trying to build a tool/prompt for a service that no longer exists.
        if (! array_key_exists($convo->service, Service::toConfig($convo->whatsapp_account_id))) {
            Log::warning('Conversation service is no longer available; returning customer to service selection', [
                'conversation_id' => $convo->id,
                'service' => $convo->service,
            ]);

            $lang = $convo->language ?? 'en';

            if ($message = $this->localizedMessage('bot_fallback_message', $lang)) {
                $wa->sendText($phone, $message);
            }

            $convo->update(['step' => 'AWAIT_SERVICE', 'service' => null, 'history' => [], 'claude_message_count' => 0]);
            $wa->sendServiceButtons($phone, $lang);

            return;
        }

        $convo->increment('claude_message_count');

        $reply = $agent->handle($convo);

        if ($reply['type'] === 'tool') {
            $serviceRequest = ServiceRequest::create([
                'whatsapp_account_id' => $convo->whatsapp_account_id,
                'wa_phone' => $phone,
                'platform' => $this->platform,
                'service' => $convo->service,
                'payload' => $reply['input'],
                'status' => 'new',
            ]);

            $this->notifyStaff($serviceRequest);

            $lang = $convo->language ?? 'en';
            $confirmation = config("services_bot.replies.confirmation.{$lang}")
                ?? config('services_bot.replies.confirmation.en');

            $wa->sendText($phone, $confirmation);
            $convo->update(['step' => 'DONE', 'history' => [], 'claude_message_count' => 0]);

            return;
        }

        $this->appendHistory($convo, 'assistant', $reply['text']);
        $wa->sendText($phone, $reply['text']);
    }

    private function sendWelcome(MessagingChannel $wa, Conversation $convo, string $phone): void
    {
        if ($welcome = $this->localizedMessage('bot_welcome_message', $convo->language)) {
            $wa->sendText($phone, $welcome);
            $this->appendHistory($convo, 'assistant', $welcome);
        }
    }

    private function appendHistory(Conversation $convo, string $role, string $content): void
    {
        $history = $convo->history ?? [];
        $history[] = ['role' => $role, 'content' => $content];
        $convo->history = array_slice($history, -20);
        $convo->save();
    }
}
