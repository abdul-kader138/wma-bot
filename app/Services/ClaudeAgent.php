<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeAgent
{
    /**
     * Returns one of:
     *   ['type' => 'text', 'text' => '...']                    -> relay to the customer
     *   ['type' => 'tool', 'name' => '...', 'input' => [...]]  -> all fields collected & confirmed
     */
    public function handle(Conversation $convo): array
    {
        $service      = Service::toConfig($convo->whatsapp_account_id)[$convo->service] ?? null;
        $languageName = config("services_bot.languages.{$convo->language}", 'English');

        $tool     = $this->buildTool($service);
        $system   = $this->buildSystemPrompt($service, $languageName);
        $messages = $this->buildMessages($convo);

        try {
            $response = Http::withHeaders([
                'x-api-key'         => Setting::getSecret('claude_api_key') ?: config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
                ->timeout(40)
                // Retry transient failures here (rate limit / overload / connection blip)
                // rather than failing the whole job, which would re-run everything from
                // scratch — including re-sending the WhatsApp reply if one already went out.
                ->retry(2, 500, function (\Throwable $e) {
                    return $e instanceof \Illuminate\Http\Client\ConnectionException
                        || ($e instanceof \Illuminate\Http\Client\RequestException
                            && in_array($e->response->status(), [429, 500, 502, 503, 529]));
                })
                ->post('https://api.anthropic.com/v1/messages', [
                    'model'       => Setting::get('claude_model') ?: config('services.anthropic.model'),
                    'max_tokens'  => (int) Setting::get('claude_max_tokens', 1024),
                    'temperature' => (float) Setting::get('claude_temperature', 0.7),
                    'system'      => $system,
                    'tools'       => [$tool],
                    'messages'    => $messages,
                ])->throw()->json();
        } catch (\Throwable $e) {
            Log::error('Claude API call failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'tool_use') {
                return ['type' => 'tool', 'name' => $block['name'], 'input' => $block['input']];
            }
        }

        $text = collect($response['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");

        if (! $text) {
            $fallbacks = Setting::get('bot_fallback_message', []);
            $text      = $fallbacks[$convo->language ?? 'en'] ?? $fallbacks['en'] ?? '…';
        }

        return ['type' => 'text', 'text' => $text];
    }

    private function buildTool(array $service): array
    {
        $properties = [];
        $required   = [];

        foreach ($service['tool']['fields'] as $name => $f) {
            $prop = ['type' => $f['type']];
            if (! empty($f['description'])) {
                $prop['description'] = $f['description'];
            }
            $properties[$name] = $prop;
            if (! empty($f['required'])) {
                $required[] = $name;
            }
        }

        return [
            'name'         => $service['tool']['name'],
            'description'  => $service['tool']['description'],
            'input_schema' => [
                'type'       => 'object',
                'properties' => $properties,
                'required'   => $required,
            ],
        ];
    }

    private function buildSystemPrompt(array $service, string $languageName): string
    {
        $lines = [];
        foreach ($service['tool']['fields'] as $name => $f) {
            $tag     = ! empty($f['required']) ? '[required]' : '[optional]';
            $desc    = $f['description'] ?? '';
            $lines[] = "- {$name} {$tag} {$desc}";
        }
        $fields   = implode("\n", $lines);
        $toolName = $service['tool']['name'];
        $label    = $service['prompt_label'];

        return <<<PROMPT
        You are a friendly assistant handling WhatsApp enquiries.
        Reply ONLY in {$languageName}. Keep every message short, like a real WhatsApp chat.

        You are helping the customer with: {$label}.

        Collect the following details, asking for ONE thing at a time in a natural order:
        {$fields}

        Rules:
        - Never invent or assume values. If the customer is unsure, guide them.
        - When you have every [required] detail, summarise them back and ask the customer to confirm.
        - Only after the customer confirms, call the `{$toolName}` tool with the collected values.
        - Never call the tool before the customer has confirmed.
        PROMPT;
    }

    private function buildMessages(Conversation $convo): array
    {
        $history = $convo->history ?? [];

        // Drop any leading assistant-only turns (e.g. a canned welcome message
        // logged for the admin transcript) — the Anthropic API rejects a message
        // list that doesn't start with role 'user'.
        while (! empty($history) && $history[0]['role'] !== 'user') {
            array_shift($history);
        }

        $messages = [];
        foreach ($history as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
        }

        if (empty($messages)) {
            $messages[] = [
                'role'    => 'user',
                'content' => '(The customer just selected this service and already received a short welcome message. Skip the greeting — ask directly for the first detail.)',
            ];
        }

        return $messages;
    }
}
