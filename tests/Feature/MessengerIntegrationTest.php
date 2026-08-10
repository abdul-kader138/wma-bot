<?php

namespace Tests\Feature;

use App\Jobs\HandleIncomingMessage;
use App\Models\Conversation;
use App\Models\Service;
use App\Models\WhatsAppAccount;
use App\Services\MessengerClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MessengerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppAccount $messengerAccount;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        $this->messengerAccount = WhatsAppAccount::create([
            'name'         => 'Test Page',
            'platform'     => 'messenger',
            'external_id'  => 'page-123',
            'access_token' => 'test-page-token',
            'api_version'  => 'v22.0',
            'is_active'    => true,
        ]);

        Service::create([
            'whatsapp_account_id' => $this->messengerAccount->id,
            'slug'             => 'ticket',
            'label'            => ['en' => 'Ticket booking', 'it' => 'Biglietti', 'bn' => 'টিকিট বুকিং'],
            'prompt_label'     => 'booking a travel ticket',
            'color'            => 'primary',
            'is_active'        => true,
            'sort_order'       => 0,
            'tool_name'        => 'submit_ticket_request',
            'tool_description' => 'Save a completed ticket booking request.',
            'tool_fields'      => [],
        ]);
    }

    public function test_send_text_posts_meta_send_api_shape(): void
    {
        (new MessengerClient($this->messengerAccount))->sendText('psid-1', 'Hello there');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v22.0/me/messages'
                && $request['recipient']['id'] === 'psid-1'
                && $request['message']['text'] === 'Hello there'
                && $request->hasHeader('Authorization', 'Bearer test-page-token');
        });
    }

    public function test_send_language_list_uses_quick_replies_capped_at_13(): void
    {
        (new MessengerClient($this->messengerAccount))->sendLanguageList('psid-1');

        Http::assertSent(function ($request) {
            $replies = $request['message']['quick_replies'];

            return count($replies) <= 13
                && collect($replies)->contains(fn ($r) => $r['payload'] === 'en');
        });
    }

    public function test_parse_incoming_plain_text(): void
    {
        $client = new MessengerClient($this->messengerAccount);

        $result = $client->parseIncoming([
            'messaging' => [[
                'sender'  => ['id' => 'psid-1'],
                'message' => ['mid' => 'mid-1', 'text' => 'hello'],
            ]],
        ]);

        $this->assertSame([
            'message_id' => 'mid-1',
            'phone'      => 'psid-1',
            'text'       => 'hello',
            'reply_id'   => null,
        ], $result);
    }

    public function test_parse_incoming_quick_reply_selection(): void
    {
        $client = new MessengerClient($this->messengerAccount);

        $result = $client->parseIncoming([
            'messaging' => [[
                'sender'  => ['id' => 'psid-1'],
                'message' => ['mid' => 'mid-2', 'text' => 'English', 'quick_reply' => ['payload' => 'en']],
            ]],
        ]);

        $this->assertSame('en', $result['reply_id']);
    }

    public function test_parse_incoming_skips_echo_of_own_sent_message(): void
    {
        $client = new MessengerClient($this->messengerAccount);

        $result = $client->parseIncoming([
            'messaging' => [[
                'sender'  => ['id' => 'psid-1'],
                'message' => ['mid' => 'mid-3', 'text' => 'sent by the page itself', 'is_echo' => true],
            ]],
        ]);

        $this->assertNull($result);
    }

    public function test_messenger_webhook_end_to_end_creates_conversation_with_platform(): void
    {
        $payload = [
            'object' => 'page',
            'entry'  => [[
                'id'        => 'page-123',
                'messaging' => [[
                    'sender'  => ['id' => 'psid-42'],
                    'message' => ['mid' => 'mid-e2e', 'text' => 'hi'],
                ]],
            ]],
        ];

        $response = $this->postJson('/api/webhook/messenger', $payload);

        $response->assertOk();

        $convo = Conversation::where('wa_phone', 'psid-42')->first();
        $this->assertNotNull($convo);
        $this->assertSame('messenger', $convo->platform);
        $this->assertSame($this->messengerAccount->id, $convo->whatsapp_account_id);
    }

    public function test_messenger_webhook_verify_handshake(): void
    {
        config(['services.messenger.verify_token' => 'secret-token']);

        $response = $this->get('/api/webhook/messenger?hub_mode=subscribe&hub_verify_token=secret-token&hub_challenge=echo-me');

        $response->assertOk();
        $response->assertSee('echo-me');
    }

    public function test_unrecognized_page_id_is_ignored_not_crashed(): void
    {
        $payload = [
            'object' => 'page',
            'entry'  => [[
                'id'        => 'unknown-page',
                'messaging' => [['sender' => ['id' => 'psid-9'], 'message' => ['mid' => 'm1', 'text' => 'hi']]],
            ]],
        ];

        $response = $this->postJson('/api/webhook/messenger', $payload);

        $response->assertOk();
        $this->assertSame(0, Conversation::count());
    }

    public function test_instagram_webhook_end_to_end_creates_conversation_with_platform(): void
    {
        $igAccount = WhatsAppAccount::create([
            'name'         => 'Test IG',
            'platform'     => 'instagram',
            'external_id'  => 'ig-account-9',
            'access_token' => 'test-ig-token',
            'api_version'  => 'v22.0',
            'is_active'    => true,
        ]);

        Service::create([
            'whatsapp_account_id' => $igAccount->id,
            'slug'             => 'ticket',
            'label'            => ['en' => 'Ticket booking'],
            'prompt_label'     => 'booking a travel ticket',
            'color'            => 'primary',
            'is_active'        => true,
            'sort_order'       => 0,
            'tool_name'        => 'submit_ticket_request',
            'tool_description' => 'Save a completed ticket booking request.',
            'tool_fields'      => [],
        ]);

        $payload = [
            'object' => 'instagram',
            'entry'  => [[
                'id'        => 'ig-account-9',
                'messaging' => [[
                    'sender'  => ['id' => 'igsid-7'],
                    'message' => ['mid' => 'mid-ig', 'text' => 'hi'],
                ]],
            ]],
        ];

        $response = $this->postJson('/api/webhook/instagram', $payload);

        $response->assertOk();

        $convo = Conversation::where('wa_phone', 'igsid-7')->first();
        $this->assertNotNull($convo);
        $this->assertSame('instagram', $convo->platform);
        $this->assertSame($igAccount->id, $convo->whatsapp_account_id);
    }

    public function test_whatsapp_and_messenger_default_accounts_do_not_clobber_each_other(): void
    {
        $whatsapp = WhatsAppAccount::create([
            'name'            => 'WA Default',
            'phone_number_id' => 'wa-phone-1',
            'access_token'    => 'x',
            'api_version'     => 'v22.0',
            'is_active'       => true,
            'is_default'      => true,
        ]);

        $this->messengerAccount->update(['is_default' => true]);

        $this->assertTrue($whatsapp->fresh()->is_default);
        $this->assertTrue($this->messengerAccount->fresh()->is_default);
    }
}
