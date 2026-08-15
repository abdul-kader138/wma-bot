<?php

namespace App\Services\Maria;

use App\Models\Setting;
use App\Services\Maria\Support\JsonSchemaValidator;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StructuredWorkflowAgent
{
    /**
     * @return array{data:array,usage:array}
     *
     * @throws RuntimeException if Claude never calls the tool, or its output doesn't satisfy $schema —
     *         callers must not persist a workflow result that failed to validate.
     */
    public function run(string $system, string $instruction, string $toolName, string $toolDescription, array $schema): array
    {
        $response = Http::withHeaders([
            'x-api-key' => Setting::getSecret('claude_api_key') ?: config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01', 'content-type' => 'application/json',
        ])->timeout(40)->retry(2, 500)->post('https://api.anthropic.com/v1/messages', [
            'model' => Setting::get('claude_model') ?: config('services.anthropic.model'),
            'max_tokens' => (int) Setting::get('claude_max_tokens', 2048),
            'temperature' => 0.1,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $instruction]],
            'tools' => [[
                'name' => $toolName, 'description' => $toolDescription, 'input_schema' => $schema,
            ]],
            'tool_choice' => ['type' => 'tool', 'name' => $toolName],
        ])->throw()->json();

        $tool = collect($response['content'] ?? [])->first(fn ($block) => ($block['type'] ?? null) === 'tool_use' && ($block['name'] ?? null) === $toolName);
        if (! $tool) {
            throw new RuntimeException("Structured workflow did not call {$toolName}.");
        }

        $data = (array) $tool['input'];
        $errors = JsonSchemaValidator::validate($data, $schema);
        if ($errors !== []) {
            throw new RuntimeException("Structured workflow output for {$toolName} failed schema validation: ".implode('; ', $errors));
        }

        return [
            'data' => $data,
            'usage' => ['input_tokens' => (int) data_get($response, 'usage.input_tokens', 0), 'output_tokens' => (int) data_get($response, 'usage.output_tokens', 0)],
        ];
    }
}
