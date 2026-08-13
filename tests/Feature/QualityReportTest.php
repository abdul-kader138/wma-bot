<?php

namespace Tests\Feature;

use App\Models\AssistantProfile;
use App\Models\MariaQualityEvent;
use App\Models\User;
use App\Models\WorkflowRun;
use App\Services\Maria\QualityReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QualityReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_distinguishes_estimated_and_verified_savings_and_summarizes_events(): void
    {
        $this->travelTo('2026-08-13 10:00:00');
        $user = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $user->id, 'timezone' => 'Europe/Berlin', 'is_active' => true]);
        WorkflowRun::create(['run_id' => fake()->uuid(), 'user_id' => $user->id, 'workflow_type' => 'content_package', 'status' => 'completed', 'estimated_manual_minutes' => 120, 'human_minutes' => 20, 'verified_time_saved_minutes' => 100, 'time_saving_verified_at' => now(), 'time_saving_verified_by' => $user->id]);
        WorkflowRun::create(['run_id' => fake()->uuid(), 'user_id' => $user->id, 'workflow_type' => 'email_triage', 'status' => 'completed', 'estimated_manual_minutes' => 60]);
        WorkflowRun::create(['run_id' => fake()->uuid(), 'user_id' => $user->id, 'workflow_type' => 'meeting_preparation', 'status' => 'failed', 'error' => 'Provider unavailable']);
        foreach (range(1, 2) as $number) {
            MariaQualityEvent::create(['user_id' => $user->id, 'reported_by' => $user->id, 'event_type' => 'correction', 'category' => 'voice', 'severity' => 'low', 'description' => "Voice correction {$number}", 'status' => 'resolved', 'occurred_at' => now()]);
        }
        MariaQualityEvent::create(['user_id' => $user->id, 'reported_by' => $user->id, 'event_type' => 'safety_incident', 'category' => 'recipient', 'severity' => 'high', 'description' => 'Incorrect recipient caught before sending', 'status' => 'resolved', 'occurred_at' => now()]);

        $first = app(QualityReportService::class)->generate($profile, '2026-08-10');
        $second = app(QualityReportService::class)->generate($profile, '2026-08-10');

        $this->assertTrue($first->is($second));
        $this->assertSame('estimated', $first->content['largest_estimated_time_savings'][0]['label']);
        $this->assertSame('verified', $first->content['largest_verified_time_savings'][0]['label']);
        $this->assertSame(100, $first->content['largest_verified_time_savings'][0]['verified_minutes']);
        $this->assertSame(2, $first->content['recurring_corrections'][0]['count']);
        $this->assertCount(1, $first->content['safety_incidents']);
        $this->assertSame('voice', $first->content['recommended_improvement']['category']);
        $this->assertDatabaseCount('assistant_briefs', 1);
    }

    public function test_quality_events_are_owner_protected(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $event = MariaQualityEvent::create(['user_id' => $owner->id, 'event_type' => 'feedback', 'category' => 'priority', 'severity' => 'low', 'description' => 'Feedback', 'status' => 'open', 'occurred_at' => now()]);
        $this->assertTrue($owner->can('view', $event));
        $this->assertTrue($other->cannot('view', $event));
    }
}
