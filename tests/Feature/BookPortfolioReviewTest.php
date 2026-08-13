<?php

namespace Tests\Feature;

use App\Models\AssistantProfile;
use App\Models\Book;
use App\Models\User;
use App\Services\Maria\BookPortfolioReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookPortfolioReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_review_preserves_book_identifiers_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $user->id, 'timezone' => 'Europe/Berlin', 'is_active' => true]);
        $book = Book::create([
            'user_id' => $user->id, 'exact_title' => 'The Exact Book', 'edition' => 'Second Edition',
            'stage' => 'editing', 'current_milestone' => 'Copy edit', 'milestone_owner' => 'Editor Name',
            'milestone_due_at' => '2026-08-21 10:00:00', 'blocker' => 'Awaiting chapter notes',
            'contributors' => ['Editor Name' => 'in progress'], 'publication_target' => '2026-12-01',
            'next_action' => 'Send chapter notes', 'status' => 'active',
        ]);
        Http::fake(['https://api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'tool_use', 'name' => 'save_book_portfolio_review', 'input' => [
                'week' => '2026-08-10', 'books' => [[
                    'book_id' => $book->id, 'exact_title' => 'The Exact Book', 'edition' => 'Second Edition',
                    'stage' => 'editing', 'milestone' => 'Copy edit', 'owner' => 'Editor Name', 'date' => '2026-08-21',
                    'blocker' => 'Awaiting chapter notes', 'contributor_status' => 'in progress', 'publication_target' => '2026-12-01',
                ]], 'highest_value_actions' => [
                    ['title' => 'Send chapter notes', 'reason' => 'Unblocks editing', 'owner' => $user->name, 'date' => '2026-08-14'],
                    ['title' => 'Confirm edit', 'reason' => 'Protect due date', 'owner' => 'Editor Name', 'date' => '2026-08-15'],
                    ['title' => 'Review launch', 'reason' => 'Prepare target', 'owner' => $user->name, 'date' => '2026-08-16'],
                ], 'source_gaps' => [],
            ]]], 'usage' => ['input_tokens' => 100, 'output_tokens' => 80],
        ])]);

        $first = app(BookPortfolioReviewService::class)->generate($profile, '2026-08-10');
        $second = app(BookPortfolioReviewService::class)->generate($profile, '2026-08-10');

        $this->assertTrue($first->is($second));
        $this->assertSame('The Exact Book', $first->content['books'][0]['exact_title']);
        $this->assertSame('Second Edition', $first->content['books'][0]['edition']);
        $this->assertCount(3, $first->content['highest_value_actions']);
        $this->assertDatabaseCount('workflow_runs', 1);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request['messages'][0]['content'], 'The Exact Book')
            && str_contains($request['messages'][0]['content'], 'Second Edition'));
    }

    public function test_book_policy_prevents_cross_owner_access(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $book = Book::create(['user_id' => $owner->id, 'exact_title' => 'Private Book', 'stage' => 'idea', 'status' => 'active']);

        $this->assertTrue($owner->can('view', $book));
        $this->assertTrue($other->cannot('view', $book));
    }
}
