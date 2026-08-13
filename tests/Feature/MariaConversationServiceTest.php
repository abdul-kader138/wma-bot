<?php

namespace Tests\Feature;

use App\Jobs\HandleIncomingMessage;
use App\Models\AssistantChannelIdentity;
use App\Models\AssistantProfile;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Services\ClaudeAgent;
use App\Services\FaqMatcher;
use App\Services\Maria\AssistantIdentityResolver;
use App\Services\Maria\MariaAgent;
use App\Services\Maria\MariaConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class MariaConversationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_conversation_persists_bounded_history_and_usage(): void
    {
        $user = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $user->id, 'timezone' => 'Europe/Berlin']);
        $account = WhatsAppAccount::create([
            'name' => 'Maria private', 'platform' => 'whatsapp',
            'phone_number_id' => 'maria-phone-id', 'external_id' => 'maria-phone-id',
        ]);
        $identity = AssistantChannelIdentity::create([
            'assistant_profile_id' => $profile->id,
            'whatsapp_account_id' => $account->id,
            'platform' => 'whatsapp',
            'external_identifier' => '4912345',
            'verified_at' => now(),
        ]);

        $agent = Mockery::mock(MariaAgent::class);
        $agent->shouldReceive('handle')->once()->withArgs(fn (User $owner, string $message, array $history) => $owner->is($user)
            && $message === 'What is next?' && $history === [])->andReturn([
                'text' => 'Your next task is the meeting brief. Status: Completed',
                'prompt_version' => 'config-v1',
                'tool_calls' => [],
                'usage' => ['input_tokens' => 30, 'output_tokens' => 12],
            ]);

        $reply = (new MariaConversationService($agent))->handle($identity, 'What is next?');

        $this->assertSame('Your next task is the meeting brief. Status: Completed', $reply);
        $this->assertDatabaseHas('assistant_conversations', [
            'assistant_profile_id' => $profile->id,
            'assistant_channel_identity_id' => $identity->id,
            'input_tokens' => 30,
            'output_tokens' => 12,
            'last_prompt_version' => 'config-v1',
        ]);
        $history = $profile->conversations()->first()->history;
        $this->assertSame('What is next?', $history[0]['content']);
        $this->assertSame($reply, $history[1]['content']);
    }

    public function test_verified_identity_routes_to_maria_instead_of_public_intake(): void
    {
        Http::fake();
        $user = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $user->id]);
        $account = WhatsAppAccount::create([
            'name' => 'Maria private', 'platform' => 'whatsapp',
            'phone_number_id' => 'private-phone-id', 'external_id' => 'private-phone-id',
            'access_token' => 'token', 'api_version' => 'v22.0',
        ]);
        AssistantChannelIdentity::create([
            'assistant_profile_id' => $profile->id, 'whatsapp_account_id' => $account->id,
            'platform' => 'whatsapp', 'external_identifier' => '4911111', 'verified_at' => now(),
        ]);
        $maria = Mockery::mock(MariaConversationService::class);
        $maria->shouldReceive('handle')->once()->andReturn('Private Maria response');
        $value = ['messages' => [[
            'id' => 'private-message-1', 'from' => '4911111', 'type' => 'text',
            'text' => ['body' => 'Show my day'],
        ]]];

        (new HandleIncomingMessage($value, $account->id))->handle(
            Mockery::mock(ClaudeAgent::class),
            app(FaqMatcher::class),
            app(AssistantIdentityResolver::class),
            $maria,
        );

        $this->assertDatabaseCount('conversations', 0);
        Http::assertSent(fn ($request) => ($request->data()['text']['body'] ?? null) === 'Private Maria response');
    }
}
