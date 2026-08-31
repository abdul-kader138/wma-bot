<?php

namespace Tests\Feature;

use App\Models\AssistantConversation;
use App\Models\AssistantMemory;
use App\Models\AssistantProfile;
use App\Models\User;
use App\Services\Maria\ConversationMemoryService;
use App\Services\Maria\Tools\RememberFactTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MariaConversationMemoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_remember_fact_tool_upserts_a_normalised_key_per_owner(): void
    {
        $owner = User::factory()->create();
        $tool = app(RememberFactTool::class);

        $tool->execute($owner, ['key' => 'Meeting Time Preference', 'value' => 'Prefers mornings']);
        $tool->execute($owner, ['key' => 'meeting_time_preference', 'value' => 'Prefers mornings before 11']);

        $this->assertDatabaseCount('assistant_memories', 1);
        $this->assertDatabaseHas('assistant_memories', [
            'user_id' => $owner->id, 'key' => 'meeting_time_preference', 'value' => 'Prefers mornings before 11',
        ]);
    }

    public function test_preamble_surfaces_owner_facts_and_the_rolling_summary(): void
    {
        $owner = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $owner->id]);
        AssistantMemory::create(['user_id' => $owner->id, 'key' => 'assistant_name', 'value' => 'Marta']);
        $conversation = AssistantConversation::create([
            'assistant_profile_id' => $profile->id, 'status' => 'active',
            'history' => [], 'summary' => 'Discussed the book launch; awaiting the cover proof.',
        ]);

        $preamble = app(ConversationMemoryService::class)->preamble($owner, $conversation);

        $this->assertStringContainsString('assistant_name: Marta', $preamble);
        $this->assertStringContainsString('awaiting the cover proof', $preamble);
        $this->assertStringContainsString('do not mention this framing', $preamble);
    }

    public function test_preamble_is_empty_when_there_is_nothing_to_recall(): void
    {
        $owner = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $owner->id]);
        $conversation = AssistantConversation::create(['assistant_profile_id' => $profile->id, 'status' => 'active', 'history' => []]);

        $this->assertSame('', app(ConversationMemoryService::class)->preamble($owner, $conversation));
    }

    public function test_compress_folds_old_turns_into_a_summary_once_history_is_long(): void
    {
        Http::fake(['https://api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'tool_use', 'name' => 'save_summary', 'id' => 's1', 'input' => [
                'summary' => 'Owner confirmed the 28 Oct launch and a 5,000 print run.',
            ]]],
            'usage' => ['input_tokens' => 40, 'output_tokens' => 15],
        ])]);

        $profile = AssistantProfile::create(['user_id' => User::factory()->create()->id]);
        $history = [];
        for ($i = 0; $i < 30; $i++) {
            $history[] = ['role' => $i % 2 === 0 ? 'user' : 'assistant', 'content' => "turn {$i}"];
        }
        $conversation = AssistantConversation::create(['assistant_profile_id' => $profile->id, 'status' => 'active', 'history' => $history]);

        [$trimmed, $summary] = app(ConversationMemoryService::class)->compress($conversation, $history);

        $this->assertLessThan(count($history), count($trimmed));
        $this->assertSame('turn 29', end($trimmed)['content']);
        $this->assertStringContainsString('5,000 print run', (string) $summary);
    }

    public function test_compress_is_a_noop_for_a_short_history(): void
    {
        Http::fake();
        $profile = AssistantProfile::create(['user_id' => User::factory()->create()->id]);
        $conversation = AssistantConversation::create(['assistant_profile_id' => $profile->id, 'status' => 'active', 'history' => []]);
        $history = [['role' => 'user', 'content' => 'hi'], ['role' => 'assistant', 'content' => 'hello']];

        [$trimmed, $summary] = app(ConversationMemoryService::class)->compress($conversation, $history);

        $this->assertSame($history, $trimmed);
        $this->assertNull($summary);
        Http::assertNothingSent();
    }
}
