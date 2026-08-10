<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Service;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MultiAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_messenger_pages_route_independently(): void
    {
        Http::fake();

        $pageA = WhatsAppAccount::create([
            'name' => 'Page A', 'platform' => 'messenger', 'external_id' => 'page-A',
            'access_token' => 'tok-a', 'api_version' => 'v22.0', 'is_active' => true,
        ]);
        $pageB = WhatsAppAccount::create([
            'name' => 'Page B', 'platform' => 'messenger', 'external_id' => 'page-B',
            'access_token' => 'tok-b', 'api_version' => 'v22.0', 'is_active' => true,
        ]);

        foreach ([$pageA, $pageB] as $acc) {
            Service::create([
                'whatsapp_account_id' => $acc->id, 'slug' => 'ticket',
                'label' => ['en' => 'Ticket'], 'prompt_label' => 'ticket', 'color' => 'primary',
                'is_active' => true, 'sort_order' => 0,
                'tool_name' => 'submit_ticket_request', 'tool_description' => 'x', 'tool_fields' => [],
            ]);
        }

        // Same customer PSID messaging two DIFFERENT pages should create two SEPARATE conversations.
        $this->postJson('/api/webhook/messenger', [
            'object' => 'page',
            'entry' => [['id' => 'page-A', 'messaging' => [['sender' => ['id' => 'psid-1'], 'message' => ['mid' => 'm1', 'text' => 'hi']]]]],
        ])->assertOk();

        $this->postJson('/api/webhook/messenger', [
            'object' => 'page',
            'entry' => [['id' => 'page-B', 'messaging' => [['sender' => ['id' => 'psid-1'], 'message' => ['mid' => 'm2', 'text' => 'hi']]]]],
        ])->assertOk();

        $this->assertSame(2, Conversation::where('wa_phone', 'psid-1')->count());
        $this->assertNotNull(Conversation::where('wa_phone', 'psid-1')->where('whatsapp_account_id', $pageA->id)->first());
        $this->assertNotNull(Conversation::where('wa_phone', 'psid-1')->where('whatsapp_account_id', $pageB->id)->first());

        // A single webhook batch containing entries for BOTH pages must route each
        // to its own account, not just process the first one.
        $this->postJson('/api/webhook/messenger', [
            'object' => 'page',
            'entry' => [
                ['id' => 'page-A', 'messaging' => [['sender' => ['id' => 'psid-2'], 'message' => ['mid' => 'm3', 'text' => 'hi']]]],
                ['id' => 'page-B', 'messaging' => [['sender' => ['id' => 'psid-3'], 'message' => ['mid' => 'm4', 'text' => 'hi']]]],
            ],
        ])->assertOk();

        $this->assertNotNull(Conversation::where('wa_phone', 'psid-2')->where('whatsapp_account_id', $pageA->id)->first());
        $this->assertNotNull(Conversation::where('wa_phone', 'psid-3')->where('whatsapp_account_id', $pageB->id)->first());
    }
}
