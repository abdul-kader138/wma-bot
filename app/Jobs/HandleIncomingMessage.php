<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\ServiceRequest;
use App\Services\ClaudeAgent;
use App\Services\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HandleIncomingMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(public array $value) {}

    public function handle(WhatsAppClient $wa, ClaudeAgent $agent): void
    {
        $msg = $wa->parseIncoming($this->value);
        if (! $msg) {
            return;
        }

        // Deduplicate: skip if we've processed this message ID already.
        if ($msg['message_id'] && ! Cache::add("wa_msg:{$msg['message_id']}", 1, now()->addHours(24))) {
            return;
        }

        $phone = $msg['phone'];
        $convo = Conversation::firstOrCreate(
            ['wa_phone' => $phone],
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
                if (! array_key_exists($input, config('services_bot.services'))) {
                    $wa->sendServiceButtons($phone, $convo->language ?? 'en');
                    break;
                }
                $convo->update(['service' => $input, 'step' => 'IN_SERVICE', 'history' => []]);
                $this->runAgent($wa, $agent, $convo, $phone);
                break;

            case 'IN_SERVICE':
                $this->appendHistory($convo, 'user', $input);
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
    }

    private function runAgent(WhatsAppClient $wa, ClaudeAgent $agent, Conversation $convo, string $phone): void
    {
        $reply = $agent->handle($convo);

        if ($reply['type'] === 'tool') {
            ServiceRequest::create([
                'wa_phone' => $phone,
                'service'  => $convo->service,
                'payload'  => $reply['input'],
                'status'   => 'new',
            ]);

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
