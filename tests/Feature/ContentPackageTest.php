<?php

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\ContentItem;
use App\Models\User;
use App\Services\Maria\ContentPackageService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContentPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_brand_claims_generate_one_idempotent_draft_package(): void
    {
        $user = User::factory()->create();
        $claimText = 'The programme served 100 participants.';
        Claim::create([
            'user_id' => $user->id, 'claim_text' => $claimText, 'subject' => 'Programme', 'category' => 'impact',
            'status' => 'verified', 'verified_at' => now(), 'recheck_at' => now()->addMonth(), 'permitted_brands' => ['All Catholic Media'],
        ]);
        $item = ContentItem::create([
            'user_id' => $user->id, 'brand' => 'All Catholic Media', 'source_idea' => 'Tell the programme story',
            'core_claims' => [$claimText], 'source_hash' => ContentPackageService::sourceHash('Tell the programme story'),
        ]);
        Http::fake(['https://api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'tool_use', 'name' => 'save_content_package', 'input' => [
                'linkedin_authority_post' => 'Authority draft', 'story_or_quotation_post' => 'Story draft',
                'podcast_outline' => ['Opening', 'Discussion'], 'video_scripts' => ['One', 'Two', 'Three'],
                'newsletter_section' => 'Newsletter draft', 'comment_angles' => ['Angle'],
                'relationship_outreach_angle' => 'Outreach draft', 'attributions' => ['Claims Registry'],
            ]]], 'usage' => ['input_tokens' => 80, 'output_tokens' => 120],
        ])]);

        $first = app(ContentPackageService::class)->generate($item);
        $second = app(ContentPackageService::class)->generate($first);

        $this->assertSame('draft', $second->status);
        $this->assertSame('Authority draft', $second->master_draft);
        $this->assertSame('Three', $second->derivatives['video_scripts'][2]);
        $this->assertDatabaseCount('workflow_runs', 1);
        Http::assertSentCount(1);
    }

    public function test_unverified_or_wrong_brand_claim_blocks_before_ai_call(): void
    {
        $user = User::factory()->create();
        $item = ContentItem::create([
            'user_id' => $user->id, 'brand' => 'Books', 'source_idea' => 'Unsupported claim idea',
            'core_claims' => ['A claim without evidence.'], 'source_hash' => ContentPackageService::sourceHash('Unsupported claim idea'),
        ]);
        Http::fake();

        try {
            app(ContentPackageService::class)->generate($item);
            $this->fail('Expected the claims gate to block generation.');
        } catch (ValidationException) {
            $this->assertSame('blocked_claims', $item->refresh()->status);
            $this->assertSame(['A claim without evidence.'], $item->claim_verification['blocked_claims']);
            Http::assertNothingSent();
        }
    }

    public function test_normalized_duplicate_source_is_rejected_per_owner_and_brand(): void
    {
        $user = User::factory()->create();
        ContentItem::create(['user_id' => $user->id, 'brand' => 'Books', 'source_idea' => 'A Core Idea', 'source_hash' => ContentPackageService::sourceHash('A Core Idea')]);

        $this->expectException(UniqueConstraintViolationException::class);
        ContentItem::create(['user_id' => $user->id, 'brand' => 'Books', 'source_idea' => '  a  core idea ', 'source_hash' => ContentPackageService::sourceHash('  a  core idea ')]);
    }
}
