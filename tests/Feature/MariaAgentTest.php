<?php

namespace Tests\Feature;

use App\Models\MariaTask;
use App\Models\PromptVersion;
use App\Models\User;
use App\Services\Maria\MariaAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MariaAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_executes_tool_and_returns_result_to_claude(): void
    {
        $owner = User::factory()->create();
        $responses = [
            [
                'content' => [[
                    'type' => 'tool_use', 'id' => 'tool-1', 'name' => 'create_task',
                    'input' => ['task' => 'Call Marco', 'owner_name' => 'Fr. Morson', 'status' => 'open'],
                ]],
                'usage' => ['input_tokens' => 100, 'output_tokens' => 20],
            ],
            [
                'content' => [['type' => 'text', 'text' => 'Task created. Status: Completed']],
                'usage' => ['input_tokens' => 120, 'output_tokens' => 15],
            ],
        ];
        Http::fake(['https://api.anthropic.com/*' => Http::sequence()->push($responses[0])->push($responses[1])]);

        $result = app(MariaAgent::class)->handle($owner, 'Remind me to call Marco.');

        $this->assertSame('Task created. Status: Completed', $result['text']);
        $this->assertSame(220, $result['usage']['input_tokens']);
        $this->assertDatabaseHas('maria_tasks', ['user_id' => $owner->id, 'task' => 'Call Marco']);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => collect($request->data()['messages'] ?? [])->contains(
            fn ($message) => $message['role'] === 'user' && is_array($message['content'])
                && ($message['content'][0]['type'] ?? null) === 'tool_result'
        ));
    }

    public function test_task_listing_is_scoped_to_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        MariaTask::create(['user_id' => $owner->id, 'task' => 'Owner task', 'owner_name' => 'Owner']);
        MariaTask::create(['user_id' => $other->id, 'task' => 'Secret other task', 'owner_name' => 'Other']);

        Http::fake(['https://api.anthropic.com/*' => Http::sequence()
            ->push(['content' => [['type' => 'tool_use', 'id' => 'tool-1', 'name' => 'list_tasks', 'input' => []]]])
            ->push(['content' => [['type' => 'text', 'text' => 'Done. Status: Completed']]])]);

        app(MariaAgent::class)->handle($owner, 'List my tasks.');

        Http::assertSent(function ($request) {
            $encoded = json_encode($request->data()['messages'] ?? []);

            return str_contains($encoded, 'Owner task') && ! str_contains($encoded, 'Secret other task');
        });
    }

    public function test_active_database_prompt_overrides_config_prompt(): void
    {
        PromptVersion::create([
            'prompt_type' => 'maria_system', 'version' => '2.0', 'content' => 'CUSTOM MARIA PROMPT',
            'content_hash' => hash('sha256', 'CUSTOM MARIA PROMPT'), 'is_active' => true,
        ]);
        Http::fake(['https://api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'Completed']]])]);

        $result = app(MariaAgent::class)->handle(User::factory()->create(), 'Hello');

        $this->assertSame('2.0', $result['prompt_version']);
        Http::assertSent(fn ($request) => $request->data()['system'] === 'CUSTOM MARIA PROMPT');
    }
}
