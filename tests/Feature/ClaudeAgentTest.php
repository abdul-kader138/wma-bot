<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Service;
use App\Models\WhatsAppAccount;
use App\Services\ClaudeAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClaudeAgentTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = WhatsAppAccount::create([
            'name' => 'Test', 'phone_number_id' => 'pid', 'access_token' => 'tok',
            'api_version' => 'v22.0', 'is_active' => true, 'is_default' => true,
        ]);

        Service::create([
            'whatsapp_account_id' => $this->account->id,
            'slug'             => 'ticket',
            'label'            => ['en' => 'Ticket booking'],
            'prompt_label'     => 'booking a travel ticket',
            'color'            => 'primary',
            'is_active'        => true,
            'sort_order'       => 0,
            'tool_name'        => 'submit_ticket_request',
            'tool_description' => 'Save a completed ticket booking request.',
            'tool_fields'      => [
                ['name' => 'full_name', 'type' => 'string', 'required' => true, 'description' => "Customer's full name"],
            ],
        ]);
    }

    public function test_leading_welcome_message_is_not_sent_as_first_history_turn(): void
    {
        Http::fake([
            'https://api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'What is your full name?']],
            ]),
        ]);

        // Mirrors what HandleIncomingMessage::sendWelcome() stores: a canned,
        // non-Claude-authored welcome message as the only, leading history entry.
        $convo = Conversation::create([
            'whatsapp_account_id' => $this->account->id,
            'wa_phone'            => '393000000099',
            'step'                => 'IN_SERVICE',
            'language'            => 'en',
            'service'             => 'ticket',
            'history'             => [['role' => 'assistant', 'content' => 'Welcome!']],
        ]);

        (new ClaudeAgent())->handle($convo);

        Http::assertSent(function ($request) {
            $messages = $request->data()['messages'] ?? [];

            // Anthropic's API rejects a request whose first message isn't role 'user'.
            return ($messages[0]['role'] ?? null) === 'user';
        });
    }
}
