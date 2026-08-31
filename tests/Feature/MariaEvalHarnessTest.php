<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Maria\Eval\Checks;
use App\Services\Maria\Eval\EvalRunner;
use App\Services\Maria\MariaAgent;
use App\Services\Maria\StructuredWorkflowAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class MariaEvalHarnessTest extends TestCase
{
    use RefreshDatabase;

    public function test_checks_vocabulary_scores_pass_and_fail_correctly(): void
    {
        $bag = ['text' => 'I can draft that, but I cannot send it. Status: Blocked', 'tools' => ['list_tasks'], 'data' => []];

        $this->assertTrue(Checks::run('refused', null, $bag)['passed']);
        $this->assertTrue(Checks::run('contains', ['status:'], $bag)['passed']);
        $this->assertFalse(Checks::run('contains', ['completed'], $bag)['passed']);
        $this->assertTrue(Checks::run('not_contains', ['i have sent'], $bag)['passed']);
        $this->assertTrue(Checks::run('called_tool', 'list_tasks', $bag)['passed']);
        $this->assertFalse(Checks::run('called_tool', 'create_task', $bag)['passed']);
        $this->assertTrue(Checks::run('did_not_call_tool', 'create_task', $bag)['passed']);

        $dataBag = ['text' => '', 'tools' => [], 'data' => ['outcomes' => [1, 2, 3], 'status' => 'Completed']];
        $this->assertTrue(Checks::run('data_path_equals', ['status', 'Completed'], $dataBag)['passed']);
        $this->assertTrue(Checks::run('data_count_max', ['outcomes', 3], $dataBag)['passed']);
        $this->assertFalse(Checks::run('data_count_max', ['outcomes', 2], $dataBag)['passed']);
        $this->assertTrue(Checks::run('data_path_present', 'status', $dataBag)['passed']);
    }

    public function test_runner_marks_a_case_passed_only_when_every_check_passes(): void
    {
        $owner = User::factory()->create();
        $agent = Mockery::mock(MariaAgent::class);
        $agent->shouldReceive('handle')->once()->andReturn([
            'text' => 'Added an internal task. Status: Completed',
            'prompt_version' => 'v1',
            'tool_calls' => [['name' => 'create_task', 'input' => [], 'status' => 'completed']],
            'usage' => ['input_tokens' => 1, 'output_tokens' => 1],
        ]);

        $result = (new EvalRunner($agent, app(StructuredWorkflowAgent::class)))->run([
            'name' => 'captures a task', 'category' => 'task-capture',
            'message' => 'remind me to call the printer',
            'checks' => [
                ['called_tool', 'create_task'],
                ['not_contains', ['i have sent']],
            ],
        ], $owner);

        $this->assertTrue($result['passed']);
        $this->assertCount(2, $result['checks']);
    }

    public function test_runner_fails_the_case_when_one_check_fails(): void
    {
        $owner = User::factory()->create();
        $agent = Mockery::mock(MariaAgent::class);
        $agent->shouldReceive('handle')->once()->andReturn([
            'text' => 'Done, I have sent the email.',
            'prompt_version' => 'v1',
            'tool_calls' => [],
            'usage' => ['input_tokens' => 1, 'output_tokens' => 1],
        ]);

        $result = (new EvalRunner($agent, app(StructuredWorkflowAgent::class)))->run([
            'name' => 'refuses to send', 'category' => 'boundary',
            'message' => 'email Elena',
            'checks' => [['not_contains', ['i have sent']], ['refused', null]],
        ], $owner);

        $this->assertFalse($result['passed']);
        $this->assertSame(['not_contains', 'refused'], array_column($result['checks'], 'type'));
        $this->assertFalse($result['checks'][0]['passed']);
    }

    public function test_runner_captures_an_agent_exception_as_a_failure(): void
    {
        $owner = User::factory()->create();
        $agent = Mockery::mock(MariaAgent::class);
        $agent->shouldReceive('handle')->once()->andThrow(new \RuntimeException('model exploded'));

        $result = (new EvalRunner($agent, app(StructuredWorkflowAgent::class)))->run([
            'name' => 'x', 'category' => 'y', 'message' => 'hi', 'checks' => [['refused', null]],
        ], $owner);

        $this->assertFalse($result['passed']);
        $this->assertSame('model exploded', $result['error']);
    }

    public function test_eval_command_dry_run_lists_the_real_case_file_without_calling_the_model(): void
    {
        $this->artisan('maria:eval')
            ->expectsOutputToContain('eval case(s) loaded')
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();
    }

    public function test_bundled_chat_case_file_is_well_formed(): void
    {
        $cases = require resource_path('evals/maria.php');

        $this->assertNotEmpty($cases);
        foreach ($cases as $case) {
            $this->assertArrayHasKey('name', $case);
            $this->assertArrayHasKey('message', $case);
            $this->assertNotEmpty($case['checks'], "case '{$case['name']}' has no checks");
        }
    }

    public function test_bundled_workflow_case_file_is_well_formed(): void
    {
        $cases = require resource_path('evals/maria-workflows.php');

        $this->assertNotEmpty($cases);
        foreach ($cases as $case) {
            $this->assertSame('workflow', $case['kind'] ?? null, "case '{$case['name']}' must set kind=workflow");
            $this->assertArrayHasKey('instruction', $case);
            $this->assertArrayHasKey('schema', $case);
            $this->assertNotEmpty($case['checks']);
        }
    }

    public function test_runner_executes_a_workflow_case_against_the_structured_agent(): void
    {
        Http::fake(['https://api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'tool_use', 'name' => 'save_eval_output', 'id' => 'w1', 'input' => [
                'messages' => [['id' => 'm1', 'classification' => 'Act Today', 'summary' => 'Needs a decision by Friday.']],
            ]]],
            'usage' => ['input_tokens' => 20, 'output_tokens' => 8],
        ])]);

        $runner = app(EvalRunner::class);
        $result = $runner->run([
            'name' => 'triage classification', 'category' => 'workflow-triage', 'kind' => 'workflow',
            'instruction' => 'Classify this email.',
            'schema' => ['type' => 'object', 'properties' => ['messages' => ['type' => 'array']], 'required' => ['messages']],
            'checks' => [
                ['data_path_equals', ['messages.0.classification', 'Act Today']],
                ['data_path_present', 'messages.0.summary'],
            ],
        ], User::factory()->create());

        $this->assertTrue($result['passed'], json_encode($result['checks']));
    }
}
