<?php

namespace Tests\Feature;

use App\Models\AgverseOpportunity;
use App\Models\AssistantProfile;
use App\Models\User;
use App\Services\Maria\AgverseOpportunityReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgverseOpportunityReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_ranks_auditable_scores_and_separates_facts_from_hypotheses(): void
    {
        $user = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $user->id, 'timezone' => 'Europe/Berlin', 'is_active' => true]);
        $high = $this->opportunity($user, 'Verified pilot', [5, 5, 5, 5, 1, 1], ['Signed pilot letter'], ['Renewal may follow']);
        $low = $this->opportunity($user, 'Early lead', [2, 2, 2, 1, 4, 4], ['Intro received'], ['Budget may exist']);

        $first = app(AgverseOpportunityReviewService::class)->review($profile, '2026-08-13');
        $second = app(AgverseOpportunityReviewService::class)->review($profile, '2026-08-13');

        $this->assertTrue($first->is($second));
        $this->assertSame($high->id, $first->content['opportunities'][0]['id']);
        $this->assertSame(['Signed pilot letter'], $first->content['opportunities'][0]['verified_facts']);
        $this->assertSame(['Renewal may follow'], $first->content['opportunities'][0]['hypotheses']);
        $this->assertTrue($first->content['opportunities'][1]['approval_required']);
        $this->assertGreaterThan($low->refresh()->priority_score, $high->refresh()->priority_score);
        $this->assertDatabaseCount('workflow_runs', 1);
    }

    public function test_opportunities_are_owner_protected(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $item = $this->opportunity($owner, 'Private', [3, 3, 3, 3, 3, 3], [], []);
        $this->assertTrue($owner->can('view', $item));
        $this->assertTrue($other->cannot('view', $item));
    }

    private function opportunity(User $user, string $name, array $scores, array $facts, array $hypotheses): AgverseOpportunity
    {
        return AgverseOpportunity::create([
            'user_id' => $user->id, 'name' => $name, 'summary' => 'Opportunity', 'value_score' => $scores[0],
            'strategic_fit_score' => $scores[1], 'urgency_score' => $scores[2], 'evidence_score' => $scores[3],
            'effort_score' => $scores[4], 'risk_score' => $scores[5], 'verified_facts' => $facts, 'hypotheses' => $hypotheses,
            'next_step' => 'Prepare reviewed proposal', 'next_step_owner' => $user->name, 'next_step_at' => now()->addDay(),
            'stage' => 'research', 'status' => 'active',
        ]);
    }
}
