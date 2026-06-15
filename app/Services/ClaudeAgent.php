<?php

namespace App\Services;

use App\Models\Conversation;
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
        $service      = config("services_bot.services.{$convo->service}");
        $languageName = config("services_bot.languages.{$convo->language}", 'English');

        $tool     = $this->buildTool($service);
        $system   = $this->buildSystemPrompt($service, $languageName);
        $messages = $this->buildMessages($convo);

        try {
            $response = Http::withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(40)->post('https://api.anthropic.com/v1/messages', [
                'model'      => config('services.anthropic.model'),
                'max_tokens' => 1024,
                'system'     => $system,
                'tools'      => [$tool],
                'messages'   => $messages,
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

        return ['type' => 'text', 'text' => $text ?: '…'];
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
        $messages = [];
        foreach (($convo->history ?? []) as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
        }

        if (empty($messages)) {
            $messages[] = [
                'role'    => 'user',
                'content' => '(The customer just selected this service. Greet them and ask for the first detail.)',
            ];
        }

        return $messages;
    }
}
