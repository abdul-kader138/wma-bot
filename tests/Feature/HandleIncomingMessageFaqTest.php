<?php

namespace Tests\Feature;

use App\Jobs\HandleIncomingMessage;
use App\Models\Conversation;
use App\Models\Faq;
use App\Services\ClaudeAgent;
use App\Services\WhatsAppClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HandleIncomingMessageFaqTest extends TestCase
{
    use RefreshDatabase;

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
        Cache::store('array')->put("wa_msg:msg1", 0);

        Faq::create([
            'service'   => null,
            'question'  => 'What is the price?',
            'keywords'  => ['price', 'cost', 'how much'],
            'answer'    => ['en' => 'Our standard fee is €50.'],
            'is_active' => true,
        ]);

        $wa    = $this->createMock(WhatsAppClient::class);
        $agent = $this->createMock(ClaudeAgent::class);

        $wa->expects($this->once())
            ->method('sendText')
            ->with($phone, 'Our standard fee is €50.');

        $agent->expects($this->never())
            ->method('handle');

        $wa->method('parseIncoming')
            ->willReturn(['message_id' => 'msg1', 'phone' => $phone, 'text' => 'how much does it cost?', 'reply_id' => null]);

        $job = new HandleIncomingMessage($this->makeValue($phone, 'how much does it cost?'));
        $job->handle($wa, $agent, app(\App\Services\FaqMatcher::class));
    }

    public function test_no_faq_match_delegates_to_claude(): void
    {
        $phone = '393001234568';
        $this->inServiceConversation($phone);

        $wa    = $this->createMock(WhatsAppClient::class);
        $agent = $this->createMock(ClaudeAgent::class);

        $agent->expects($this->once())
            ->method('handle')
            ->willReturn(['type' => 'text', 'text' => 'What is your travel date?']);

        $wa->method('parseIncoming')
            ->willReturn(['message_id' => 'msg2', 'phone' => $phone, 'text' => 'My name is John', 'reply_id' => null]);

        $wa->method('sendText');

        $job = new HandleIncomingMessage($this->makeValue($phone, 'My name is John', 'msg2'));
        $job->handle($wa, $agent, app(\App\Services\FaqMatcher::class));
    }

    public function test_service_scoped_faq_not_matched_for_different_service(): void
    {
        $phone = '393001234569';
        Conversation::create([
            'wa_phone' => $phone,
            'step'     => 'IN_SERVICE',
            'language' => 'en',
            'service'  => 'license',
            'history'  => [],
        ]);

        Faq::create([
            'service'   => 'ticket',
            'question'  => 'Ticket price?',
            'keywords'  => ['price', 'cost'],
            'answer'    => ['en' => 'Ticket costs €50.'],
            'is_active' => true,
        ]);

        $wa    = $this->createMock(WhatsAppClient::class);
        $agent = $this->createMock(ClaudeAgent::class);

        $agent->expects($this->once())
            ->method('handle')
            ->willReturn(['type' => 'text', 'text' => 'Please provide your name.']);

        $wa->method('parseIncoming')
            ->willReturn(['message_id' => 'msg3', 'phone' => $phone, 'text' => 'what is the price?', 'reply_id' => null]);

        $wa->method('sendText');

        $job = new HandleIncomingMessage($this->makeValue($phone, 'what is the price?', 'msg3'));
        $job->handle($wa, $agent, app(\App\Services\FaqMatcher::class));
    }
}
