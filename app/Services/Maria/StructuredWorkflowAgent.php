<?php

namespace App\Services\Maria;

use App\Models\Setting;
use App\Services\Maria\Support\JsonSchemaValidator;
use RuntimeException;

class StructuredWorkflowAgent
{
    /** One corrective round is allowed when the first tool output fails schema validation. */
    private const MAX_ATTEMPTS = 2;

    public function __construct(
        private readonly ClaudeGate $gate,
        private readonly ClaudeClient $client,
    ) {}

    /**
     * @return array{data:array,usage:array}
     *
     * @throws RuntimeException if Claude never calls the tool, or its output still doesn't satisfy
     *                          $schema after one repair attempt — callers must not persist an unvalidated result.
     */
    public function run(string $system, string $instruction, string $toolName, string $toolDescription, array $schema): array
    {
        $messages = [['role' => 'user', 'content' => $instruction]];
        $usage = ['input_tokens' => 0, 'output_tokens' => 0];
        $lastErrors = [];

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $this->gate->reserve();
            $response = $this->call($system, $messages, $toolName, $toolDescription, $schema);
            $usage['input_tokens'] += (int) data_get($response, 'usage.input_tokens', 0);
            $usage['output_tokens'] += (int) data_get($response, 'usage.output_tokens', 0);

            $tool = collect($response['content'] ?? [])->first(
                fn ($block) => ($block['type'] ?? null) === 'tool_use' && ($block['name'] ?? null) === $toolName
            );
            if (! $tool) {
                throw new RuntimeException("Structured workflow did not call {$toolName}.");
            }

            $data = (array) $tool['input'];
            $lastErrors = JsonSchemaValidator::validate($data, $schema);
            if ($lastErrors === []) {
                return ['data' => $data, 'usage' => $usage];
            }

            // Feed the invalid attempt and its errors back for one correction.
            $messages[] = ['role' => 'assistant', 'content' => $response['content']];
            $messages[] = ['role' => 'user', 'content' => [[
                'type' => 'tool_result',
                'tool_use_id' => $tool['id'],
                'is_error' => true,
                'content' => 'That output failed schema validation. Call '.$toolName.' again with corrected input. Errors: '.implode('; ', $lastErrors),
            ]]];
        }

        throw new RuntimeException("Structured workflow output for {$toolName} failed schema validation after a repair attempt: ".implode('; ', $lastErrors));
    }

    private function call(string $system, array $messages, string $toolName, string $toolDescription, array $schema): array
    {
        return $this->client->messages([
            'max_tokens' => (int) Setting::get('claude_max_tokens', 2048),
            'temperature' => 0.1,
            'system' => [['type' => 'text', 'text' => $system, 'cache_control' => ['type' => 'ephemeral']]],
            'messages' => $messages,
            'tools' => [[
                'name' => $toolName, 'description' => $toolDescription, 'input_schema' => $schema,
            ]],
            'tool_choice' => ['type' => 'tool', 'name' => $toolName],
        ]);
    }
}
