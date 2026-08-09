<?php

namespace App\Services;

use App\Models\Service;
use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppClient
{
    private string $url;
    private string $token;
    private int $accountId;

    public function __construct(WhatsAppAccount $account)
    {
        $this->url       = "https://graph.facebook.com/{$account->api_version}/{$account->phone_number_id}/messages";
        $this->token     = $account->access_token;
        $this->accountId = $account->id;
    }

    public function sendText(string $to, string $body): void
    {
        $this->post([
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['body' => $body],
        ]);
    }

    public function sendLanguageList(string $to): void
    {
        $rows = [];
        foreach (config('services_bot.languages') as $code => $name) {
            $rows[] = ['id' => $code, 'title' => $name];
        }

        $this->post([
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'list',
                'body'   => ['text' => 'Please choose your language / Scegli la lingua / ভাষা নির্বাচন করুন'],
                'action' => [
                    'button'   => 'Select',
                    'sections' => [['title' => 'Languages', 'rows' => $rows]],
                ],
            ],
        ]);
    }

    public function sendServiceButtons(string $to, string $lang): void
    {
        $rows = [];
        foreach (Service::toConfig($this->accountId) as $key => $svc) {
            $title  = $svc['label'][$lang] ?? $svc['label']['en'];
            $rows[] = ['id' => $key, 'title' => $title];
        }

        $prompt = config("services_bot.replies.choose_service.{$lang}")
            ?? config('services_bot.replies.choose_service.en');

        $this->post([
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'list',
                'body'   => ['text' => $prompt],
                'action' => [
                    'button'   => 'Select',
                    // WhatsApp list messages support up to 10 rows — take the slice here
                    // only to stay within that API limit, not an arbitrary app choice.
                    'sections' => [['title' => 'Services', 'rows' => array_slice($rows, 0, 10)]],
                ],
            ],
        ]);
    }

    public function parseIncoming(array $value): ?array
    {
        $message = $value['messages'][0] ?? null;
        if (! $message) {
            return null;
        }

        // Deduplicate: callers may store processed message IDs to skip here.
        $type    = $message['type'] ?? '';
        $text    = null;
        $replyId = null;

        if ($type === 'text') {
            $text = $message['text']['body'] ?? null;
        } elseif ($type === 'interactive') {
            $interactive = $message['interactive'] ?? [];
            $replyId     = $interactive['list_reply']['id']
                ?? $interactive['button_reply']['id']
                ?? null;
        }

        return [
            'message_id' => $message['id'] ?? null,
            'phone'      => $message['from'],
            'text'       => $text,
            'reply_id'   => $replyId,
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
            Log::error('WhatsApp send failed', ['error' => $e->getMessage(), 'payload' => $payload]);
            throw $e;
        }
    }
}
