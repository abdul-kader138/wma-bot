<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Service;
use App\Models\Setting;
use App\Models\ServiceRequest;
use App\Models\WhatsAppAccount;
use App\Notifications\BotJobFailedNotification;
use App\Notifications\NewServiceRequestNotification;
use App\Services\ClaudeAgent;
use App\Services\FaqMatcher;
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

class HandleIncomingMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;

    // Headroom for: lock wait (up to 10s) + Claude call (up to 40s) + WhatsApp send (up to 10s) + overhead.
    public int $timeout = 90;

    public function __construct(public array $value, public int $whatsAppAccountId) {}

    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function handle(ClaudeAgent $agent, FaqMatcher $faqs): void
    {
        $account = WhatsAppAccount::findOrFail($this->whatsAppAccountId);

        if (! $account->is_active) {
            Log::warning('Skipping message for deactivated WhatsApp account', [
                'whatsapp_account_id' => $account->id,
            ]);

            return;
        }

        $wa = new WhatsAppClient($account);

        $msg = $wa->parseIncoming($this->value);
        if (! $msg) {
            return;
        }

        // Deduplicate: skip if we've processed this message ID already.
        if ($msg['message_id'] && ! Cache::add("wa_msg:{$msg['message_id']}", 1, now()->addHours(24))) {
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
            $lock->block(10, function () use ($agent, $faqs, $account, $phone, $msg, $wa) {
                $this->processMessage($agent, $faqs, $account, $phone, $msg, $wa);
            });
        } catch (LockTimeoutException) {
            // Another message from this same customer is still being processed right
            // now. Put this one back on the queue briefly rather than racing it.
            $this->release(3);
        }
    }

    private function processMessage(
        ClaudeAgent $agent,
        FaqMatcher $faqs,
        WhatsAppAccount $account,
        string $phone,
        array $msg,
        WhatsAppClient $wa,
    ): void {
        $convo = Conversation::firstOrCreate(
            ['wa_phone' => $phone, 'whatsapp_account_id' => $account->id],
            ['step' => 'NEW', 'history' => []]
        );

        $input = $msg['reply_id'] ?? trim((string) ($msg['text'] ?? ''));

        if (in_array(mb_strtolower($input), ['menu', 'start', 'restart', 'hi', 'hello', 'ciao', 'hola'])) {
            $convo->update(['step' => 'NEW', 'service' => null, 'history' => []]);
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
                if (! array_key_exists($input, Service::toConfig())) {
                    $wa->sendServiceButtons($phone, $convo->language ?? 'en');
                    break;
                }
                $convo->update(['service' => $input, 'step' => 'IN_SERVICE', 'history' => []]);
                $this->runAgent($wa, $agent, $convo, $phone);
                break;

            case 'IN_SERVICE':
                $this->appendHistory($convo, 'user', $input);

                if ($faq = $faqs->match($input, $convo->service)) {
                    $answer = $faq->answerFor($convo->language ?? 'en');
                    $wa->sendText($phone, $answer);
                    $this->appendHistory($convo, 'assistant', $answer);
                    break;
                }

                $this->runAgent($wa, $agent, $convo, $phone);
                break;

            case 'DONE':
            default:
                $convo->update(['step' => 'AWAIT_LANG', 'history' => []]);
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

    private function runAgent(WhatsAppClient $wa, ClaudeAgent $agent, Conversation $convo, string $phone): void
    {
        $reply = $agent->handle($convo);

        if ($reply['type'] === 'tool') {
            $serviceRequest = ServiceRequest::create([
                'whatsapp_account_id' => $convo->whatsapp_account_id,
                'wa_phone'            => $phone,
                'service'             => $convo->service,
                'payload'             => $reply['input'],
                'status'              => 'new',
            ]);

            $this->notifyStaff($serviceRequest);

            $lang         = $convo->language ?? 'en';
            $confirmation = config("services_bot.replies.confirmation.{$lang}")
                ?? config('services_bot.replies.confirmation.en');

            $wa->sendText($phone, $confirmation);
            $convo->update(['step' => 'DONE', 'history' => []]);

            return;
        }

        $this->appendHistory($convo, 'assistant', $reply['text']);
        $wa->sendText($phone, $reply['text']);
    }

    private function appendHistory(Conversation $convo, string $role, string $content): void
    {
        $history   = $convo->history ?? [];
        $history[] = ['role' => $role, 'content' => $content];
        $convo->history = array_slice($history, -20);
        $convo->save();
    }
}
