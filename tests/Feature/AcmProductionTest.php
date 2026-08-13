<?php

namespace Tests\Feature;

use App\Models\AcmProductionPlan;
use App\Models\Claim;
use App\Models\User;
use App\Services\Maria\AcmProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcmProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_claims_generate_one_draft_weekly_package(): void
    {
        $user = User::factory()->create();
        $claim = 'The event welcomed 500 attendees.';
        Claim::create(['user_id' => $user->id, 'claim_text' => $claim, 'subject' => 'Event', 'category' => 'impact', 'status' => 'verified', 'verified_at' => now(), 'recheck_at' => now()->addMonth(), 'permitted_brands' => ['All Catholic Media']]);
        $plan = AcmProductionPlan::create(['user_id' => $user->id, 'week_start' => '2026-08-10', 'theme' => 'Hope', 'core_claims' => [$claim], 'owner_name' => $user->name, 'approval_deadline' => '2026-08-14 12:00:00', 'status' => 'planned']);
        Http::fake(['https://api.anthropic.com/*' => Http::response(['content' => [['type' => 'tool_use', 'name' => 'save_acm_production_plan', 'input' => [
            'theme' => 'Hope', 'podcast_or_reflection_plan' => ['Opening', 'Reflection'], 'newsletter_sections' => ['Lead'],
            'social_package' => [['platform' => 'Instagram', 'draft' => 'Draft']], 'assets' => [['asset' => 'Cover', 'owner' => 'Designer']],
            'owners' => [['work' => 'Podcast', 'owner' => $user->name]], 'approval_deadline' => '2026-08-14T12:00:00+02:00',
            'proposed_publication_schedule' => [['channel' => 'Podcast', 'date' => '2026-08-16']], 'attributions' => ['Claims Registry'],
        ]]], 'usage' => ['input_tokens' => 50, 'output_tokens' => 90]])]);
        $first = app(AcmProductionService::class)->generate($plan);
        $second = app(AcmProductionService::class)->generate($first);
        $this->assertSame('draft', $second->status);
        $this->assertSame('Hope', $second->production_package['theme']);
        $this->assertDatabaseCount('workflow_runs', 1);
        Http::assertSentCount(1);
    }

    public function test_unverified_claim_blocks_before_generation(): void
    {
        $user = User::factory()->create();
        $plan = AcmProductionPlan::create(['user_id' => $user->id, 'week_start' => '2026-08-10', 'theme' => 'Theme', 'core_claims' => ['Unsupported'], 'owner_name' => $user->name, 'approval_deadline' => now()->addDay(), 'status' => 'planned']);
        Http::fake();
        $this->expectException(ValidationException::class);
        try {
            app(AcmProductionService::class)->generate($plan);
        } finally {
            $this->assertSame('blocked_claims', $plan->refresh()->status);
            Http::assertNothingSent();
        }
    }
}
