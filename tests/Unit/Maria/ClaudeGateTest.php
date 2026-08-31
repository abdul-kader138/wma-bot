<?php

namespace Tests\Unit\Maria;

use App\Models\AssistantChannelIdentity;
use App\Models\AssistantConversation;
use App\Models\AssistantProfile;
use App\Models\ClaudeDailyUsage;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Services\Maria\ClaudeGate;
use App\Services\Maria\ClaudeRateLimited;
use App\Services\Maria\MariaConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ClaudeGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserve_allows_calls_up_to_the_configured_per_minute_limit_then_blocks(): void
    {
        Setting::set('claude_rate_limit_per_minute', 3, 'claude');
        $gate = app(ClaudeGate::class);

        $gate->reserve();
        $gate->reserve();
        $gate->reserve();

        $this->expectException(ClaudeRateLimited::class);
        $gate->reserve();
    }

    public function test_maria_and_the_public_bot_share_one_budget(): void
    {
        Setting::set('claude_rate_limit_per_minute', 1, 'claude');

        // Simulate a public-bot call having already spent the minute's budget.
        RateLimiter::hit('claude-api-calls', 60);

        $this->expectException(ClaudeRateLimited::class);
        app(ClaudeGate::class)->reserve();
    }

    public function test_maria_daily_call_cap_blocks_further_calls_for_the_day(): void
    {
        Setting::set('claude_rate_limit_per_minute', 100, 'claude');
        Setting::set('maria_daily_call_cap', 2, 'maria');
        $gate = app(ClaudeGate::class);

        $gate->reserve();
        $gate->reserve();
        $this->assertSame(2, ClaudeGate::mariaCallsToday());

        $this->expectException(ClaudeRateLimited::class);
        $gate->reserve();
    }

    public function test_app_wide_daily_global_cap_also_stops_maria(): void
    {
        Setting::set('claude_rate_limit_per_minute', 100, 'claude');
        Setting::set('claude_daily_global_cap', 5, 'claude');
        ClaudeDailyUsage::create([
            'whatsapp_account_id' => WhatsAppAccount::create([
                'name' => 'x', 'platform' => 'whatsapp', 'phone_number_id' => 'x', 'external_id' => 'x',
            ])->id,
            'phone' => '1', 'date' => today()->toDateString(), 'count' => 5, 'platform' => 'whatsapp',
        ]);

        $this->expectException(ClaudeRateLimited::class);
        app(ClaudeGate::class)->reserve();
    }

    public function test_chat_returns_a_retry_message_instead_of_failing_when_rate_limited(): void
    {
        Http::fake();
        Setting::set('claude_rate_limit_per_minute', 1, 'claude');
        RateLimiter::hit('claude-api-calls', 60);

        $user = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $user->id, 'timezone' => 'Europe/Rome']);
        $account = WhatsAppAccount::create([
            'name' => 'Maria', 'platform' => 'whatsapp',
            'phone_number_id' => 'p', 'external_id' => 'p',
        ]);
        $identity = AssistantChannelIdentity::create([
            'assistant_profile_id' => $profile->id, 'whatsapp_account_id' => $account->id,
            'platform' => 'whatsapp', 'external_identifier' => '49999', 'verified_at' => now(),
        ]);

        $reply = app(MariaConversationService::class)->handle($identity, 'What is due today?');

        $this->assertStringContainsString('request limit', $reply);
        Http::assertNothingSent();
        // A rate-limited turn must not be persisted or billed.
        $this->assertDatabaseCount('assistant_conversations', 1);
        $this->assertSame([], AssistantConversation::first()->history);
    }
}
