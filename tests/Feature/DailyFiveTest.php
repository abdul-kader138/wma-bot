<?php

namespace Tests\Feature;

use App\Models\AssistantProfile;
use App\Models\MariaContact;
use App\Models\RelationshipRecommendation;
use App\Models\User;
use App\Models\WorkflowRun;
use App\Services\Maria\DailyFiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyFiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_selects_at_most_five_verified_contacts_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $user->id, 'timezone' => 'Europe/Berlin', 'enabled_workflows' => ['daily_five'], 'is_active' => true]);
        foreach (range(1, 6) as $number) {
            MariaContact::create([
                'user_id' => $user->id, 'full_name' => "Verified {$number}", 'tier' => $number < 3 ? 'A' : 'B',
                'stage' => 'research', 'why_matters' => 'Strategic relationship', 'verification_source' => "CRM record {$number}",
                'follow_up_at' => now()->subDay(),
            ]);
        }
        MariaContact::create(['user_id' => $user->id, 'full_name' => 'Unverified', 'tier' => 'A', 'stage' => 'research']);

        $service = app(DailyFiveService::class);
        $first = $service->generate($profile, '2026-08-13');
        $second = $service->generate($profile, '2026-08-13');

        $this->assertCount(5, $first);
        $this->assertCount(5, $second);
        $this->assertSame(5, RelationshipRecommendation::count());
        $this->assertSame(1, WorkflowRun::where('workflow_type', 'daily_five_relationships')->count());
        $this->assertFalse(RelationshipRecommendation::whereHas('contact', fn ($query) => $query->where('full_name', 'Unverified'))->exists());
    }

    public function test_recommendations_are_owner_scoped(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $contact = MariaContact::create(['user_id' => $owner->id, 'full_name' => 'Owner Contact', 'stage' => 'research', 'verification_source' => 'CRM']);
        $recommendation = RelationshipRecommendation::create([
            'user_id' => $owner->id, 'maria_contact_id' => $contact->id, 'recommendation_date' => now()->toDateString(),
            'relevance' => 'Relevant', 'recommended_stage' => 'engage',
        ]);

        $this->assertTrue($other->cannot('view', $recommendation));
        $this->assertTrue($owner->can('view', $recommendation));
    }
}
