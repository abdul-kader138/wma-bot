<?php

namespace App\Services;

use App\Contracts\MessagingChannel;
use App\Models\Service;
use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared Send API / webhook-parsing logic for Messenger and Instagram — both ride the
 * same Graph API "/me/messages" endpoint and an identical webhook payload shape
 * (entry[].messaging[]), unlike WhatsApp's own Cloud API shape. MessengerClient and
 * InstagramClient are currently empty subclasses; the split exists so a genuine
 * platform-specific quirk (e.g. Instagram's stricter messaging-window rules) has
 * somewhere to live without disturbing the other platform.
 */
abstract class MetaMessagingClient implements MessagingChannel
{
    private string $url;

    private string $token;

    protected int $accountId;

    public function __construct(WhatsAppAccount $account)
    {
        $this->url = "https://graph.facebook.com/{$account->api_version}/me/messages";
        $this->token = $account->access_token;
        $this->accountId = $account->id;
    }

    public function sendText(string $to, string $body): void
    {
        $this->post([
            'recipient' => ['id' => $to],
            'messaging_type' => 'RESPONSE',
            'message' => ['text' => $body],
        ]);
    }

    public function sendLanguageList(string $to): void
    {
        $replies = [];
        foreach (config('services_bot.languages') as $code => $name) {
            $replies[] = ['content_type' => 'text', 'title' => $name, 'payload' => $code];
        }

        $this->post([
            'recipient' => ['id' => $to],
            'messaging_type' => 'RESPONSE',
            'message' => [
                'text' => 'Please choose your language / Scegli la lingua / ভাষা নির্বাচন করুন',
                // Meta caps quick replies at 13 per message — same reasoning as
                // WhatsApp's 10-row list limit, just a different API ceiling.
                'quick_replies' => array_slice($replies, 0, 13),
            ],
        ]);
    }

    public function sendServiceButtons(string $to, string $lang): void
    {
        $replies = [];
        foreach (Service::toConfig($this->accountId) as $key => $svc) {
            $title = $svc['label'][$lang] ?? $svc['label']['en'];
            $replies[] = ['content_type' => 'text', 'title' => $title, 'payload' => $key];
        }

        if ($replies === []) {
            Log::warning(static::class.' has no active services to send', [
                'account_id' => $this->accountId,
            ]);

            $this->sendText($to, match ($lang) {
                'it' => 'Al momento non ci sono servizi disponibili. Riprova più tardi.',
                'bn' => 'এই মুহূর্তে কোনো সেবা উপলব্ধ নেই। অনুগ্রহ করে পরে আবার চেষ্টা করুন।',
                default => 'No services are available right now. Please try again later.',
            });

            return;
        }

        $prompt = config("services_bot.replies.choose_service.{$lang}")
            ?? config('services_bot.replies.choose_service.en');

        $this->post([
            'recipient' => ['id' => $to],
            'messaging_type' => 'RESPONSE',
            'message' => [
                'text' => $prompt,
                'quick_replies' => array_slice($replies, 0, 13),
            ],
        ]);
    }

    public function parseIncoming(array $value): ?array
    {
        $event = $value['messaging'][0] ?? null;
        $message = $event['message'] ?? null;
        $postback = $event['postback'] ?? null;

        // is_echo: Meta also delivers a copy of messages the page itself sent (e.g. sent
        // from the Meta inbox UI, not through this bot) — skip those, they're not customer input.
        // Postbacks (including Messenger's Get Started button) do not contain a message
        // object, so accept either shape instead of silently dropping postback events.
        if (! $event || (! $message && ! $postback) || ($message['is_echo'] ?? false)) {
            return null;
        }

        return [
            // 'phone' holds whatever this platform's external contact identifier is —
            // a Messenger PSID / Instagram IGSID here, not an actual phone number. Named
            // to match WhatsApp's shape since that's the contract MessagingChannel
            // callers already expect; see Conversation.wa_phone for the same tradeoff.
            'message_id' => $message['mid'] ?? $postback['mid'] ?? null,
            'phone' => $event['sender']['id'] ?? null,
            'text' => $message['text'] ?? null,
            'reply_id' => $message['quick_reply']['payload'] ?? $postback['payload'] ?? null,
        ];
    }

    private function post(array $payload): void
    {
        try {
            Http::withToken($this->token)
                ->acceptJson()
                ->timeout(10)
                ->post($this->url, $payload)
                ->throw();
        } catch (\Throwable $e) {
            Log::error(static::class.' send failed', ['error' => $e->getMessage(), 'payload' => $payload]);
            throw $e;
        }
    }
}
