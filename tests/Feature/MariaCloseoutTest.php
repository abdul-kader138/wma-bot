<?php

namespace Tests\Feature;

use App\Models\AssistantProfile;
use App\Models\MariaContact;
use App\Models\MariaTask;
use App\Models\Meeting;
use App\Models\User;
use App\Services\Maria\CommitmentTaskService;
use App\Services\Maria\ContactMatcher;
use App\Services\Maria\EveningReviewService;
use App\Services\Maria\MeetingCloseoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MariaCloseoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_matcher_refuses_ambiguous_same_name(): void
    {
        $owner = User::factory()->create();
        MariaContact::create(['user_id' => $owner->id, 'full_name' => 'John Smith', 'email' => 'one@example.com']);
        MariaContact::create(['user_id' => $owner->id, 'full_name' => 'John Smith', 'email' => 'two@example.com']);

        $ambiguous = app(ContactMatcher::class)->match($owner, null, ' john   smith ');
        $exact = app(ContactMatcher::class)->match($owner, 'TWO@example.com', 'John Smith');

        $this->assertSame('ambiguous', $ambiguous['status']);
        $this->assertCount(2, $ambiguous['candidates']);
        $this->assertSame('matched', $exact['status']);
        $this->assertSame('two@example.com', $exact['contact']->email);
    }

    public function test_task_deduplication_is_deterministic_but_does_not_silently_merge(): void
    {
        $owner = User::factory()->create();
        $service = app(CommitmentTaskService::class);
        $first = $service->create($owner, ['task' => 'Send the document', 'owner_name' => 'Owner', 'due_at' => '2026-08-20'], 'email', 'm1');
        $second = $service->create($owner, ['task' => ' Send   the document! ', 'owner_name' => 'Owner', 'due_at' => '2026-08-20'], 'email', 'm2');

        $this->assertSame($first->deduplication_key, $second->deduplication_key);
        $this->assertSame('duplicate_review', $second->status);
        $this->assertSame($first->id, $second->possible_duplicate_of_id);
        $this->assertDatabaseCount('maria_tasks', 2);
    }

    public function test_meeting_closeout_creates_tasks_and_unsent_thank_you_draft(): void
    {
        $owner = User::factory()->create();
        $meeting = Meeting::create([
            'user_id' => $owner->id, 'calendar_event_id' => 'event-1', 'title' => 'Publisher meeting',
            'starts_at' => now()->subHour(), 'ends_at' => now(),
        ]);
        Http::fake(['https://api.anthropic.com/*' => Http::response(['content' => [[
            'type' => 'tool_use', 'name' => 'save_meeting_closeout', 'id' => 'close-1',
            'input' => [
                'decisions' => ['Send proposal'],
                'action_items' => [['task' => 'Send proposal', 'owner_name' => $owner->name, 'due_at' => now()->addDay()->toIso8601String()]],
                'unanswered_questions' => ['Publication date'], 'thank_you_draft' => 'Thank you for the meeting.',
            ],
        ]]])]);

        $closed = app(MeetingCloseoutService::class)->close($meeting, 'We agreed to send the proposal tomorrow.');

        $this->assertSame('closed_out', $closed->preparation_status);
        $this->assertSame('Thank you for the meeting.', $closed->thank_you_draft);
        $this->assertDatabaseHas('maria_tasks', ['user_id' => $owner->id, 'task' => 'Send proposal', 'source' => 'meeting']);
    }

    public function test_evening_review_is_idempotent_per_owner_and_day(): void
    {
        $owner = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $owner->id, 'timezone' => 'Europe/Berlin']);
        MariaTask::create(['user_id' => $owner->id, 'task' => 'Completed work', 'owner_name' => $owner->name, 'status' => 'completed']);
        Http::fake(['https://api.anthropic.com/*' => Http::response(['content' => [[
            'type' => 'tool_use', 'name' => 'save_evening_review', 'id' => 'evening-1',
            'input' => [
                'date' => now('Europe/Berlin')->toDateString(), 'completed' => ['Completed work'],
                'awaiting_approval' => [], 'waiting_on_others' => [], 'unfinished' => [],
                'tomorrow_top_three' => [], 'status' => 'Completed',
            ],
        ]]])]);

        $first = app(EveningReviewService::class)->generate($profile);
        $second = app(EveningReviewService::class)->generate($profile);

        $this->assertTrue($first->is($second));
        $this->assertDatabaseCount('assistant_briefs', 1);
        Http::assertSentCount(1);
    }
}
