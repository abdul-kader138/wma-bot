<?php

namespace Tests\Feature;

use App\Jobs\Maria\GenerateMorningBrief;
use App\Jobs\Maria\MonitorDeadlines;
use App\Models\AssistantProfile;
use App\Models\MariaTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MariaWorkflowGatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_runs_workflow_defaults_core_workflows_on_when_never_configured(): void
    {
        $profile = new AssistantProfile(['enabled_workflows' => null]);

        $this->assertTrue($profile->runsWorkflow('morning_brief'));
        $this->assertTrue($profile->runsWorkflow('deadline_monitor'));
        $this->assertFalse($profile->runsWorkflow('daily_five'));
    }

    public function test_runs_workflow_honours_an_explicit_list_exactly(): void
    {
        $profile = new AssistantProfile(['enabled_workflows' => ['morning_brief', 'daily_five']]);

        $this->assertTrue($profile->runsWorkflow('morning_brief'));
        $this->assertTrue($profile->runsWorkflow('daily_five'));
        $this->assertFalse($profile->runsWorkflow('email_triage'));
        $this->assertFalse($profile->runsWorkflow('deadline_monitor'));
    }

    public function test_empty_list_switches_every_workflow_off(): void
    {
        $profile = new AssistantProfile(['enabled_workflows' => []]);

        $this->assertFalse($profile->runsWorkflow('morning_brief'));
        $this->assertFalse($profile->runsWorkflow('deadline_monitor'));
    }

    public function test_morning_brief_job_is_a_noop_when_the_workflow_is_disabled(): void
    {
        Http::fake();
        $user = User::factory()->create();
        $profile = AssistantProfile::create([
            'user_id' => $user->id, 'timezone' => 'Europe/Rome',
            'is_active' => true, 'enabled_workflows' => ['evening_review'],
        ]);

        GenerateMorningBrief::dispatchSync($profile->id, now('Europe/Rome')->toDateString());

        $this->assertDatabaseCount('assistant_briefs', 0);
        $this->assertDatabaseCount('workflow_runs', 0);
        Http::assertNothingSent();
    }

    public function test_deadline_monitor_job_is_a_noop_when_the_profile_is_inactive(): void
    {
        $user = User::factory()->create();
        $profile = AssistantProfile::create([
            'user_id' => $user->id, 'timezone' => 'Europe/Rome',
            'is_active' => false, 'enabled_workflows' => ['deadline_monitor'],
        ]);
        MariaTask::create(['user_id' => $user->id, 'task' => 'Overdue thing', 'owner_name' => $user->name, 'due_at' => now()->subDays(2)]);

        MonitorDeadlines::dispatchSync($profile->id, now('Europe/Rome')->format('Y-m-d-H'));

        $this->assertDatabaseCount('assistant_alerts', 0);
    }

    public function test_deadline_monitor_job_runs_when_enabled_and_active(): void
    {
        $user = User::factory()->create();
        $profile = AssistantProfile::create([
            'user_id' => $user->id, 'timezone' => 'Europe/Rome',
            'is_active' => true, 'enabled_workflows' => ['deadline_monitor'],
        ]);
        MariaTask::create(['user_id' => $user->id, 'task' => 'Overdue thing', 'owner_name' => $user->name, 'due_at' => now()->subDays(2)]);

        MonitorDeadlines::dispatchSync($profile->id, now('Europe/Rome')->format('Y-m-d-H'));

        $this->assertDatabaseHas('assistant_alerts', ['user_id' => $user->id, 'severity' => 'high']);
    }
}
