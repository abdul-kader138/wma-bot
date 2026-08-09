<?php

namespace Tests\Feature;

use App\Jobs\HandleIncomingMessage;
use App\Models\Conversation;
use App\Models\Faq;
use App\Models\Service;
use App\Models\WhatsAppAccount;
use App\Services\ClaudeAgent;
use App\Services\FaqMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HandleIncomingMessageFaqTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        $this->account = WhatsAppAccount::create([
            'name'            => 'Test',
            'phone_number_id' => 'test-phone-id',
            'access_token'    => 'test-token',
            'api_version'     => 'v22.0',
            'is_active'       => true,
            'is_default'      => true,
        ]);

        foreach (['ticket', 'license'] as $slug) {
            Service::create([
                'whatsapp_account_id' => $this->account->id,
                'slug'                => $slug,
                'label'               => ['en' => ucfirst($slug)],
                'prompt_label'        => $slug,
                'color'               => 'primary',
                'is_active'           => true,
                'sort_order'          => 0,
                'tool_name'           => "submit_{$slug}_request",
                'tool_description'    => "Save a completed {$slug} request.",
                'tool_fields'         => [
                    ['name' => 'full_name', 'type' => 'string', 'required' => true, 'description' => "Customer's full name"],
                ],
            ]);
        }
    }

    private function makeValue(string $from, string $text, string $messageId = 'msg1'): array
    {
        return [
            'messages' => [[
                'id'   => $messageId,
                'from' => $from,
                'type' => 'text',
                'text' => ['body' => $text],
            ]],
        ];
    }

    private function inServiceConversation(string $phone): Conversation
    {
        return Conversation::create([
            'whatsapp_account_id' => $this->account->id,
            'wa_phone' => $phone,
            'step'     => 'IN_SERVICE',
            'language' => 'en',
            'service'  => 'ticket',
            'history'  => [
                ['role' => 'user',      'content' => 'I want to book a ticket'],
                ['role' => 'assistant', 'content' => 'What is your full name?'],
            ],
        ]);
    }

    public function test_faq_match_replies_with_stored_answer_and_skips_claude(): void
    {
        $phone = '393001234567';
        $this->inServiceConversation($phone);

        Faq::create([
            'whatsapp_account_id' => $this->account->id,
            'service'   => null,
            'question'  => ['en' => 'What is the price?'],
            'keywords'  => ['price', 'cost', 'how much'],
            'answer'    => ['en' => 'Our standard fee is €50.'],
            'is_active' => true,
        ]);

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->expects($this->never())
            ->method('handle');

        $job = new HandleIncomingMessage($this->makeValue($phone, 'how much does it cost?'), $this->account->id);
        $job->handle($agent, app(FaqMatcher::class));

        Http::assertSent(function ($request) use ($phone) {
            $payload = $request->data();

            return ($payload['to'] ?? null) === $phone
                && ($payload['text']['body'] ?? null) === 'Our standard fee is €50.';
        });
    }

    public function test_no_faq_match_delegates_to_claude(): void
    {
        $phone = '393001234568';
        $this->inServiceConversation($phone);

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->expects($this->once())
            ->method('handle')
            ->willReturn(['type' => 'text', 'text' => 'What is your travel date?']);

        $job = new HandleIncomingMessage($this->makeValue($phone, 'My name is John', 'msg2'), $this->account->id);
        $job->handle($agent, app(FaqMatcher::class));
    }

    public function test_service_scoped_faq_not_matched_for_different_service(): void
    {
        $phone = '393001234569';
        Conversation::create([
            'whatsapp_account_id' => $this->account->id,
            'wa_phone' => $phone,
            'step'     => 'IN_SERVICE',
            'language' => 'en',
            'service'  => 'license',
            'history'  => [],
        ]);

        Faq::create([
            'whatsapp_account_id' => $this->account->id,
            'service'   => 'ticket',
            'question'  => ['en' => 'Ticket price?'],
            'keywords'  => ['price', 'cost'],
            'answer'    => ['en' => 'Ticket costs €50.'],
            'is_active' => true,
        ]);

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->expects($this->once())
            ->method('handle')
            ->willReturn(['type' => 'text', 'text' => 'Please provide your name.']);

        $job = new HandleIncomingMessage($this->makeValue($phone, 'what is the price?', 'msg3'), $this->account->id);
        $job->handle($agent, app(FaqMatcher::class));
    }
}
