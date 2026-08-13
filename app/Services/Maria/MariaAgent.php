<?php

namespace App\Services\Maria;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class MariaAgent
{
    public function __construct(
        private readonly ToolRegistry $tools,
        private readonly PromptResolver $prompts,
    ) {}

    /** @return array{text:string, prompt_version:string, tool_calls:array, usage:array} */
    public function handle(User $owner, string $message, array $history = []): array
    {
        $prompt = $this->prompts->active();
        $messages = $this->normalizeHistory($history);
        $messages[] = ['role' => 'user', 'content' => $message];
        $toolCalls = [];
        $usage = ['input_tokens' => 0, 'output_tokens' => 0];
        $maxRounds = max(1, (int) config('maria.max_tool_rounds', 6));

        for ($round = 0; $round < $maxRounds; $round++) {
            $response = $this->callClaude($prompt['content'], $messages);
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
                    $tool = $this->tools->get($name);
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

    private function callClaude(string $system, array $messages): array
    {
        return Http::withHeaders([
            'x-api-key' => Setting::get('claude_api_key') ?: config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(40)->retry(2, 500, function (Throwable $error) {
            return $error instanceof ConnectionException
                || ($error instanceof RequestException && in_array($error->response->status(), [429, 500, 502, 503, 529], true));
        })->post('https://api.anthropic.com/v1/messages', [
            'model' => Setting::get('claude_model') ?: config('services.anthropic.model'),
            'max_tokens' => (int) Setting::get('claude_max_tokens', 1024),
            'temperature' => (float) Setting::get('claude_temperature', 0.2),
            'system' => $system,
            'tools' => $this->tools->definitions(),
            'messages' => $messages,
        ])->throw()->json();
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
