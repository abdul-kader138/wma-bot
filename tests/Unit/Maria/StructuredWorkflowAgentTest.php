<?php

namespace Tests\Unit\Maria;

use App\Services\Maria\StructuredWorkflowAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class StructuredWorkflowAgentTest extends TestCase
{
    use RefreshDatabase;

    private function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'date' => ['type' => 'string'], 'status' => ['type' => 'string'],
        ], 'required' => ['date', 'status']];
    }

    public function test_returns_data_when_output_satisfies_the_schema(): void
    {
        Http::fake(['https://api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'tool_use', 'name' => 'save_thing', 'id' => 't1', 'input' => [
                'date' => '2026-08-15', 'status' => 'Completed',
            ]]],
            'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
        ])]);

        $result = app(StructuredWorkflowAgent::class)->run('system', 'do it', 'save_thing', 'desc', $this->schema());

        $this->assertSame(['date' => '2026-08-15', 'status' => 'Completed'], $result['data']);
    }

    public function test_rejects_output_missing_a_required_field_instead_of_persisting_it(): void
    {
        Http::fake(['https://api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'tool_use', 'name' => 'save_thing', 'id' => 't1', 'input' => [
                'date' => '2026-08-15',
            ]]],
            'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
        ])]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('failed schema validation');

        app(StructuredWorkflowAgent::class)->run('system', 'do it', 'save_thing', 'desc', $this->schema());
    }
}
