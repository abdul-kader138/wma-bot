<?php

namespace Tests\Unit\Maria;

use App\Models\AssistantProfile;
use App\Models\ConnectorAccount;
use App\Models\User;
use App\Services\Maria\ToolRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ToolRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_tools_are_always_available(): void
    {
        $owner = User::factory()->create();
        $names = collect(app(ToolRegistry::class)->definitions($owner))->pluck('name');

        foreach ([
            'list_tasks', 'create_task', 'complete_task', 'update_task',
            'list_projects', 'list_meetings', 'list_pending_approvals', 'get_latest_brief',
            'remember_fact',
        ] as $tool) {
            $this->assertTrue($names->contains($tool), "{$tool} should always be available");
        }
    }

    public function test_relationship_followup_tool_requires_the_daily_five_workflow(): void
    {
        $owner = User::factory()->create();
        $withoutProfile = collect(app(ToolRegistry::class)->definitions($owner))->pluck('name');
        $this->assertFalse($withoutProfile->contains('list_relationship_followups'));

        AssistantProfile::create(['user_id' => $owner->id, 'enabled_workflows' => ['daily_five']]);
        $withWorkflow = collect(app(ToolRegistry::class)->definitions($owner->refresh()))->pluck('name');
        $this->assertTrue($withWorkflow->contains('list_relationship_followups'));
    }

    public function test_connector_backed_tools_are_hidden_without_a_google_connector(): void
    {
        $owner = User::factory()->create();
        AssistantProfile::create([
            'user_id' => $owner->id,
            'enabled_workflows' => ['email_triage', 'meeting_preparation'],
        ]);

        $names = collect(app(ToolRegistry::class)->definitions($owner))->pluck('name');

        $this->assertFalse($names->contains('list_priority_emails'));
        $this->assertFalse($names->contains('list_calendar_events'));
        $this->assertFalse($names->contains('search_drive_files'));
    }

    public function test_email_and_calendar_tools_require_their_workflow_to_be_enabled(): void
    {
        $owner = User::factory()->create();
        AssistantProfile::create(['user_id' => $owner->id, 'enabled_workflows' => []]);
        ConnectorAccount::create([
            'user_id' => $owner->id, 'provider' => 'google', 'provider_account_id' => 'sub-1',
            'status' => 'active', 'scopes' => [],
        ]);

        $names = collect(app(ToolRegistry::class)->definitions($owner))->pluck('name');

        $this->assertFalse($names->contains('list_priority_emails'));
        $this->assertFalse($names->contains('list_calendar_events'));
        $this->assertTrue($names->contains('search_drive_files'), 'Drive search only depends on the connector, not a workflow toggle.');
    }

    public function test_connector_backed_tools_appear_once_workflow_and_connector_are_present(): void
    {
        $owner = User::factory()->create();
        AssistantProfile::create(['user_id' => $owner->id, 'enabled_workflows' => ['email_triage', 'meeting_preparation']]);
        ConnectorAccount::create([
            'user_id' => $owner->id, 'provider' => 'google', 'provider_account_id' => 'sub-1',
            'status' => 'active', 'scopes' => [],
        ]);

        $names = collect(app(ToolRegistry::class)->definitions($owner))->pluck('name');

        $this->assertTrue($names->contains('list_priority_emails'));
        $this->assertTrue($names->contains('list_calendar_events'));
    }

    public function test_get_rejects_a_tool_that_is_not_available_to_the_owner(): void
    {
        $owner = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(ToolRegistry::class)->get('list_priority_emails', $owner);
    }

    public function test_get_rejects_an_unknown_tool_name(): void
    {
        $owner = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(ToolRegistry::class)->get('not_a_real_tool', $owner);
    }
}
