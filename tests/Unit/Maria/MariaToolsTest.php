<?php

namespace Tests\Unit\Maria;

use App\Models\Approval;
use App\Models\AssistantBrief;
use App\Models\MariaProject;
use App\Models\MariaTask;
use App\Models\Meeting;
use App\Models\User;
use App\Models\WorkflowRun;
use App\Services\Maria\Tools\CompleteTaskTool;
use App\Services\Maria\Tools\LatestBriefTool;
use App\Services\Maria\Tools\ListApprovalsTool;
use App\Services\Maria\Tools\ListMeetingsTool;
use App\Services\Maria\Tools\ListProjectsTool;
use App\Services\Maria\Tools\UpdateTaskTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MariaToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_task_closes_only_an_owned_task(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $task = MariaTask::create(['user_id' => $owner->id, 'task' => 'Approve cover proof', 'owner_name' => 'Owner', 'status' => 'open']);
        $foreign = MariaTask::create(['user_id' => $other->id, 'task' => 'Foreign', 'owner_name' => 'Other', 'status' => 'open']);

        $tool = app(CompleteTaskTool::class);

        $result = $tool->execute($owner, ['task_id' => $task->id, 'note' => 'Signed off']);
        $this->assertSame('completed', $result['status']);
        $this->assertFalse($result['already_completed']);
        $this->assertDatabaseHas('maria_tasks', ['id' => $task->id, 'status' => 'completed', 'priority_reason' => 'Signed off']);

        $foreignResult = $tool->execute($owner, ['task_id' => $foreign->id]);
        $this->assertArrayHasKey('error', $foreignResult);
        $this->assertDatabaseHas('maria_tasks', ['id' => $foreign->id, 'status' => 'open']);
    }

    public function test_list_projects_is_owner_scoped_and_hides_finished_work_by_default(): void
    {
        $owner = User::factory()->create();
        MariaProject::create(['user_id' => $owner->id, 'domain' => 'BKS', 'name' => 'Autumn launch', 'desired_outcome' => 'Ship', 'owner_name' => 'Owner', 'next_action' => 'Start', 'next_action_at' => now(), 'stage' => 'active', 'status' => 'scheduled']);
        MariaProject::create(['user_id' => $owner->id, 'domain' => 'BKS', 'name' => 'Old launch', 'desired_outcome' => 'Done', 'owner_name' => 'Owner', 'next_action' => 'None', 'next_action_at' => now(), 'stage' => 'archived', 'status' => 'completed']);
        MariaProject::create(['user_id' => User::factory()->create()->id, 'domain' => 'BKS', 'name' => 'Secret', 'desired_outcome' => 'Hidden', 'owner_name' => 'Other', 'next_action' => 'Hidden', 'next_action_at' => now(), 'stage' => 'active', 'status' => 'scheduled']);

        $names = collect(app(ListProjectsTool::class)->execute($owner, []))->pluck('name');

        $this->assertTrue($names->contains('Autumn launch'));
        $this->assertFalse($names->contains('Old launch'));
        $this->assertFalse($names->contains('Secret'));
    }

    public function test_list_pending_approvals_excludes_decided_and_expired(): void
    {
        $owner = User::factory()->create();
        Approval::create(['user_id' => $owner->id, 'action_type' => 'send_email', 'proposed_action' => 'Send reply', 'proposed_content' => [], 'decision' => 'pending', 'expires_at' => now()->addHour(), 'content_hash' => 'a']);
        Approval::create(['user_id' => $owner->id, 'action_type' => 'send_email', 'proposed_action' => 'Old', 'proposed_content' => [], 'decision' => 'pending', 'expires_at' => now()->subHour(), 'content_hash' => 'b']);
        Approval::create(['user_id' => $owner->id, 'action_type' => 'send_email', 'proposed_action' => 'Approved', 'proposed_content' => [], 'decision' => 'approved', 'expires_at' => now()->addHour(), 'content_hash' => 'c']);

        $rows = app(ListApprovalsTool::class)->execute($owner, []);

        $this->assertCount(1, $rows);
        $this->assertSame('Send reply', $rows[0]['proposed_action']);
    }

    public function test_get_latest_brief_returns_the_newest_brief_of_a_type(): void
    {
        $owner = User::factory()->create();
        $run = WorkflowRun::create(['run_id' => (string) Str::uuid(), 'user_id' => $owner->id, 'workflow_type' => 'morning_brief', 'status' => 'completed', 'started_at' => now()]);
        AssistantBrief::create(['user_id' => $owner->id, 'workflow_run_id' => $run->id, 'type' => 'morning', 'brief_date' => now()->subDay()->toDateString(), 'content' => ['status' => 'old']]);
        AssistantBrief::create(['user_id' => $owner->id, 'workflow_run_id' => $run->id, 'type' => 'morning', 'brief_date' => now()->toDateString(), 'content' => ['status' => 'Completed']]);

        $result = app(LatestBriefTool::class)->execute($owner, ['type' => 'morning']);

        $this->assertSame(now()->toDateString(), $result['brief_date']);
        $this->assertSame('Completed', $result['content']['status']);

        $this->assertArrayHasKey('error', app(LatestBriefTool::class)->execute($owner, ['type' => 'evening']));
    }

    public function test_update_task_reschedules_restatuses_and_relinks_only_owned_tasks(): void
    {
        $owner = User::factory()->create();
        $project = MariaProject::create([
            'user_id' => $owner->id, 'domain' => 'BKS', 'name' => 'Autumn launch', 'desired_outcome' => 'Ship',
            'owner_name' => 'Owner', 'next_action' => 'Start', 'next_action_at' => now(), 'stage' => 'active', 'status' => 'scheduled',
        ]);
        $task = MariaTask::create(['user_id' => $owner->id, 'task' => 'Do the thing', 'owner_name' => 'Owner', 'status' => 'open']);

        $result = app(UpdateTaskTool::class)->execute($owner, [
            'task_id' => $task->id, 'status' => 'waiting',
            'due_at' => '2026-09-20T09:00:00+02:00', 'project_name' => 'Autumn launch',
        ]);

        $this->assertSame('waiting', $result['status']);
        $this->assertSame($project->id, $result['project_id']);
        $this->assertDatabaseHas('maria_tasks', ['id' => $task->id, 'status' => 'waiting', 'project_id' => $project->id]);

        $foreign = MariaTask::create(['user_id' => User::factory()->create()->id, 'task' => 'X', 'owner_name' => 'Y', 'status' => 'open']);
        $this->assertArrayHasKey('error', app(UpdateTaskTool::class)->execute($owner, ['task_id' => $foreign->id, 'status' => 'blocked']));
        $this->assertArrayHasKey('error', app(UpdateTaskTool::class)->execute($owner, ['task_id' => $task->id, 'project_name' => 'No such project']));
    }

    public function test_list_meetings_returns_synced_upcoming_meetings_for_the_owner(): void
    {
        $owner = User::factory()->create();
        Meeting::create(['user_id' => $owner->id, 'calendar_event_id' => 'a', 'title' => 'Soon', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'preparation_status' => 'prepared']);
        Meeting::create(['user_id' => $owner->id, 'calendar_event_id' => 'b', 'title' => 'Way off', 'starts_at' => now()->addDays(40), 'ends_at' => now()->addDays(40)->addHour(), 'preparation_status' => 'pending']);
        Meeting::create(['user_id' => $owner->id, 'calendar_event_id' => 'c', 'title' => 'Past', 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDays(2)->addHour(), 'preparation_status' => 'closed_out']);
        Meeting::create(['user_id' => User::factory()->create()->id, 'calendar_event_id' => 'd', 'title' => 'Someone else', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'preparation_status' => 'prepared']);

        $titles = collect(app(ListMeetingsTool::class)->execute($owner, ['days_ahead' => 7]))->pluck('title');

        $this->assertSame(['Soon'], $titles->all());
    }
}
