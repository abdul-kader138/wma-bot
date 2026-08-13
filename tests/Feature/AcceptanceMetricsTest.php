<?php

namespace Tests\Feature;

use App\Models\AssistantProfile;
use App\Models\MariaContact;
use App\Models\MariaProject;
use App\Models\MariaQualityEvent;
use App\Models\RelationshipRecommendation;
use App\Models\User;
use App\Models\WorkflowRun;
use App\Services\Maria\AcceptanceMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcceptanceMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_metrics_use_recorded_denominators_and_do_not_invent_missing_measurements(): void
    {
        $user = User::factory()->create();
        AssistantProfile::create(['user_id' => $user->id, 'timezone' => 'Europe/Berlin', 'is_active' => true]);
        WorkflowRun::create(['run_id' => fake()->uuid(), 'user_id' => $user->id, 'workflow_type' => 'content_package', 'status' => 'completed', 'estimated_manual_minutes' => 120, 'verified_time_saved_minutes' => 90, 'time_saving_verified_at' => now(), 'time_saving_verified_by' => $user->id]);
        MariaQualityEvent::create(['user_id' => $user->id, 'event_type' => 'correction', 'category' => 'voice', 'severity' => 'low', 'description' => 'Major voice edit', 'status' => 'resolved', 'occurred_at' => now()]);
        $contact = MariaContact::create(['user_id' => $user->id, 'full_name' => 'Contact', 'stage' => 'research']);
        RelationshipRecommendation::create(['user_id' => $user->id, 'maria_contact_id' => $contact->id, 'recommendation_date' => now(), 'relevance' => 'Relevant', 'recommended_stage' => 'engage', 'status' => 'accepted']);
        MariaProject::create(['user_id' => $user->id, 'domain' => 'BKS', 'name' => 'Complete project', 'desired_outcome' => 'Done', 'stage' => 'active', 'priority' => 'high', 'owner_name' => $user->name, 'next_action' => 'Review', 'next_action_at' => now()->addDay(), 'status' => 'waiting']);

        $report = app(AcceptanceMetricsService::class)->calculate($user);
        $metrics = collect($report['metrics'])->keyBy('name');

        $this->assertSame('not_measured', $metrics['Critical tracked deadline recall']['status']);
        $this->assertSame(100.0, $metrics['Relationship recommendation acceptance']['value']);
        $this->assertSame('pass', $metrics['Active projects with owner/action/date']['status']);
        $this->assertSame(120, $report['operations']['estimated_minutes_saved']);
        $this->assertSame(90, $report['operations']['verified_minutes_saved']);
    }

    public function test_daily_snapshot_is_auditable_and_idempotent(): void
    {
        $user = User::factory()->create();
        $service = app(AcceptanceMetricsService::class);

        $first = $service->snapshot($user);
        $second = $service->snapshot($user);

        $this->assertTrue($first->is($second));
        $this->assertSame('acceptance_30_day', $first->type);
        $this->assertDatabaseCount('assistant_briefs', 1);
        $this->assertDatabaseCount('workflow_runs', 1);
    }
}
