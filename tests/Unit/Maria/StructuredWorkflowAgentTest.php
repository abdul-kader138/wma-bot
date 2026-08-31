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

    public function test_repairs_an_invalid_first_attempt_on_a_second_round(): void
    {
        Http::fake(['https://api.anthropic.com/*' => Http::sequence()
            ->push([
                'content' => [['type' => 'tool_use', 'name' => 'save_thing', 'id' => 't1', 'input' => ['date' => '2026-08-15']]],
                'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
            ])
            ->push([
                'content' => [['type' => 'tool_use', 'name' => 'save_thing', 'id' => 't2', 'input' => ['date' => '2026-08-15', 'status' => 'Completed']]],
                'usage' => ['input_tokens' => 12, 'output_tokens' => 6],
            ])]);

        $result = app(StructuredWorkflowAgent::class)->run('system', 'do it', 'save_thing', 'desc', $this->schema());

        $this->assertSame(['date' => '2026-08-15', 'status' => 'Completed'], $result['data']);
        $this->assertSame(22, $result['usage']['input_tokens']);
        $this->assertSame(11, $result['usage']['output_tokens']);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => collect($request->data()['messages'] ?? [])->contains(
            fn ($m) => is_array($m['content']) && ($m['content'][0]['type'] ?? null) === 'tool_result'
                && str_contains($m['content'][0]['content'] ?? '', 'failed schema validation')
        ));
    }
}
