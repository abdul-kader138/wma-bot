<?php

namespace Tests\Feature;

use App\Jobs\SendIdleFollowUps;
use App\Models\Conversation;
use App\Models\Service;
use App\Models\Setting;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendIdleFollowUpsTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        $this->account = WhatsAppAccount::create([
            'name' => 'Test', 'phone_number_id' => 'pid', 'access_token' => 'tok',
            'api_version' => 'v22.0', 'is_active' => true, 'is_default' => true,
        ]);

        Service::create([
            'whatsapp_account_id' => $this->account->id,
            'slug' => 'ticket', 'label' => ['en' => 'Ticket booking'],
            'prompt_label' => 'booking a travel ticket', 'color' => 'primary',
            'is_active' => true, 'sort_order' => 0,
            'tool_name' => 'submit_ticket_request', 'tool_description' => 'desc',
            'tool_fields' => [],
        ]);

        Setting::set('bot_idle_follow_up_hours', 4, 'bot');
        Setting::set('bot_idle_follow_up_message', ['en' => 'Still there?'], 'bot');
    }

    public function test_nudges_a_conversation_idle_past_the_threshold(): void
    {
        $convo = Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => '393000000010',
            'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => [],
            'last_customer_message_at' => now()->subHours(5),
        ]);

        app(SendIdleFollowUps::class)->handle();

        Http::assertSent(fn ($request) => ($request->data()['to'] ?? null) === '393000000010'
            && str_contains($request->data()['text']['body'] ?? '', 'Still there?'));

        $this->assertNotNull($convo->refresh()->idle_nudged_at);
    }

    public function test_does_not_nudge_before_the_threshold(): void
    {
        Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => '393000000011',
            'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => [],
            'last_customer_message_at' => now()->subHours(1),
        ]);

        app(SendIdleFollowUps::class)->handle();

        Http::assertNothingSent();
    }

    public function test_does_not_nudge_past_the_24h_whatsapp_window(): void
    {
        Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => '393000000012',
            'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => [],
            'last_customer_message_at' => now()->subHours(30),
        ]);

        app(SendIdleFollowUps::class)->handle();

        Http::assertNothingSent();
    }

    public function test_does_not_nudge_twice_for_the_same_quiet_spell(): void
    {
        Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => '393000000013',
            'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => [],
            'last_customer_message_at' => now()->subHours(5),
            'idle_nudged_at' => now()->subHours(1),
        ]);

        app(SendIdleFollowUps::class)->handle();

        Http::assertNothingSent();
    }

    public function test_escalated_conversations_are_never_nudged(): void
    {
        Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => '393000000014',
            'step' => 'HUMAN', 'language' => 'en', 'service' => 'ticket', 'history' => [],
            'last_customer_message_at' => now()->subHours(5),
            'escalated_at' => now()->subHours(5), 'escalation_status' => 'pending',
        ]);

        app(SendIdleFollowUps::class)->handle();

        Http::assertNothingSent();
    }

    public function test_disabled_when_hours_setting_is_zero(): void
    {
        Setting::set('bot_idle_follow_up_hours', 0, 'bot');

        Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => '393000000015',
            'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => [],
            'last_customer_message_at' => now()->subHours(5),
        ]);

        app(SendIdleFollowUps::class)->handle();

        Http::assertNothingSent();
    }
}
