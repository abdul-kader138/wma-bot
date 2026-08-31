<?php

namespace App\Services\Maria;

use App\Models\Setting;
use App\Models\User;
use RuntimeException;
use Throwable;

class MariaAgent
{
    public function __construct(
        private readonly ToolRegistry $tools,
        private readonly PromptResolver $prompts,
        private readonly ClaudeGate $gate,
        private readonly ClaudeClient $client,
    ) {}

    /**
     * @param  array{version:string, content:string}|null  $promptOverride  Used by the eval harness to test a
     *                                                                      specific prompt version without activating it.
     * @return array{text:string, prompt_version:string, tool_calls:array, usage:array}
     */
    public function handle(User $owner, string $message, array $history = [], ?array $promptOverride = null): array
    {
        $prompt = $promptOverride ?? $this->prompts->active();
        $messages = $this->normalizeHistory($history);
        $messages[] = ['role' => 'user', 'content' => $message];
        $toolCalls = [];
        $usage = ['input_tokens' => 0, 'output_tokens' => 0];
        $maxRounds = max(1, (int) config('maria.max_tool_rounds', 6));

        for ($round = 0; $round < $maxRounds; $round++) {
            $response = $this->callClaude($prompt['content'], $messages, $owner);
            $usage['input_tokens'] += (int) data_get($response, 'usage.input_tokens', 0);
            $usage['output_tokens'] += (int) data_get($response, 'usage.output_tokens', 0);
            $content = $response['content'] ?? [];
            $requestedTools = collect($content)->where('type', 'tool_use')->values();

            if ($requestedTools->isEmpty()) {
                $text = collect($content)->where('type', 'text')->pluck('text')->implode("\n");

                if ($text === '') {
                    throw new RuntimeException('Maria returned neither text nor a tool request.');
                }

                return ['text' => $text, 'prompt_version' => $prompt['version'], 'tool_calls' => $toolCalls, 'usage' => $usage];
            }

            // Anthropic requires the complete assistant content followed by one user
            // turn containing a tool_result block for every tool_use block.
            $messages[] = ['role' => 'assistant', 'content' => $content];
            $results = [];

            foreach ($requestedTools as $request) {
                $name = (string) ($request['name'] ?? '');
                $input = (array) ($request['input'] ?? []);

                try {
                    $tool = $this->tools->get($name, $owner);
                    if ($tool->requiresApproval()) {
                        throw new RuntimeException('This tool requires approval and cannot execute in the reasoning loop.');
                    }
                    $result = $tool->execute($owner, $input);
                    $toolCalls[] = ['name' => $name, 'input' => $input, 'status' => 'completed'];
                    $results[] = ['type' => 'tool_result', 'tool_use_id' => $request['id'], 'content' => json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)];
                } catch (Throwable $error) {
                    report($error);
                    $toolCalls[] = ['name' => $name, 'input' => $input, 'status' => 'failed'];
                    $results[] = ['type' => 'tool_result', 'tool_use_id' => $request['id'], 'is_error' => true, 'content' => 'Tool execution failed. Do not claim the action completed.'];
                }
            }

            $messages[] = ['role' => 'user', 'content' => $results];
        }

        throw new RuntimeException('Maria exceeded the maximum number of tool rounds.');
    }

    private function callClaude(string $system, array $messages, User $owner): array
    {
        $this->gate->reserve();

        return $this->client->messages([
            'max_tokens' => (int) Setting::get('claude_max_tokens', 1024),
            'temperature' => (float) Setting::get('claude_temperature', 0.2),
            // The system prompt and tool schemas are identical on every turn and
            // large; caching them cuts input-token cost sharply across the
            // multi-round tool loop and busy days.
            'system' => [['type' => 'text', 'text' => $system, 'cache_control' => ['type' => 'ephemeral']]],
            'tools' => $this->tools->definitions($owner),
            'messages' => $messages,
        ]);
    }

    private function normalizeHistory(array $history): array
    {
        return collect($history)->filter(fn ($turn) => is_array($turn)
            && in_array($turn['role'] ?? null, ['user', 'assistant'], true)
            && isset($turn['content']))
            ->map(fn ($turn) => ['role' => $turn['role'], 'content' => $turn['content']])
            ->values()->all();
    }
}
