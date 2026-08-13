<?php

namespace Tests\Feature;

use App\Models\AssistantAlert;
use App\Models\AssistantProfile;
use App\Models\Claim;
use App\Models\MariaTask;
use App\Models\User;
use App\Services\Maria\ClaimsVerificationService;
use App\Services\Maria\DeadlineMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MariaGovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unchanged_deadline_state_does_not_create_duplicate_alerts(): void
    {
        $user = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $user->id, 'timezone' => 'Europe/Berlin']);
        MariaTask::create([
            'user_id' => $user->id, 'task' => 'Submit report', 'owner_name' => $user->name,
            'due_at' => now()->subHour(), 'status' => 'open',
        ]);

        $monitor = app(DeadlineMonitorService::class);
        $first = $monitor->run($profile);
        $second = $monitor->run($profile);

        $this->assertSame($first[0]->id, $second[0]->id);
        $this->assertDatabaseCount('assistant_alerts', 1);
        $this->assertSame('high', AssistantAlert::first()->severity);
    }

    public function test_completed_task_resolves_existing_alert(): void
    {
        $user = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $user->id]);
        $task = MariaTask::create([
            'user_id' => $user->id, 'task' => 'Submit report', 'owner_name' => $user->name,
            'due_at' => now()->subHour(), 'status' => 'open',
        ]);
        $monitor = app(DeadlineMonitorService::class);
        $monitor->run($profile);

        $task->update(['status' => 'completed']);
        $monitor->run($profile);

        $this->assertSame('resolved', AssistantAlert::first()->status);
    }

    public function test_claim_gate_requires_current_verified_brand_authorization(): void
    {
        $user = User::factory()->create();
        Claim::create([
            'user_id' => $user->id, 'claim_text' => 'Agverse has a verified UAE pilot.',
            'subject' => 'Agverse', 'category' => 'partnership', 'status' => 'verified',
            'source_url' => 'https://example.test/evidence', 'verified_at' => now(),
            'recheck_at' => now()->addMonth(), 'permitted_brands' => ['Agverse AI UAE'],
        ]);

        $service = app(ClaimsVerificationService::class);
        $allowed = $service->verify($user, ['Agverse has a verified UAE pilot.'], 'Agverse AI UAE');
        $blocked = $service->verify($user, ['Agverse has a verified UAE pilot.'], 'All Catholic Media');

        $this->assertTrue($allowed['allowed']);
        $this->assertFalse($blocked['allowed']);
        $this->assertSame(['Agverse has a verified UAE pilot.'], $blocked['blocked_claims']);
    }
}
