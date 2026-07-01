<?php

namespace Tests\Feature;

use App\Jobs\HandleIncomingMessage;
use App\Models\Conversation;
use App\Models\Setting;
use App\Notifications\NewServiceRequestNotification;
use App\Services\ClaudeAgent;
use App\Services\FaqMatcher;
use App\Services\WhatsAppClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HandleIncomingMessageStateTest extends TestCase
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

    private function handleMessage(string $phone, string $text, string $messageId, WhatsAppClient $wa, ?ClaudeAgent $agent = null): void
    {
        $agent ??= $this->createMock(ClaudeAgent::class);

        $job = new HandleIncomingMessage($this->makeValue($phone, $text, $messageId));
        $job->handle($wa, $agent, app(FaqMatcher::class));
    }

    public function test_new_conversation_receives_language_list_and_moves_to_await_lang(): void
    {
        $phone = '393000000001';

        $wa = $this->createMock(WhatsAppClient::class);
        $wa->method('parseIncoming')->willReturn(['message_id' => 'm1', 'phone' => $phone, 'text' => 'hi', 'reply_id' => null]);
        $wa->expects($this->once())->method('sendLanguageList')->with($phone);

        $this->handleMessage($phone, 'hi', 'm1', $wa);

        $this->assertSame('AWAIT_LANG', Conversation::where('wa_phone', $phone)->first()->step);
    }

    public function test_invalid_language_reply_resends_language_list_and_stays_await_lang(): void
    {
        $phone = '393000000002';
        Conversation::create(['wa_phone' => $phone, 'step' => 'AWAIT_LANG', 'history' => []]);

        $wa = $this->createMock(WhatsAppClient::class);
        $wa->method('parseIncoming')->willReturn(['message_id' => 'm2', 'phone' => $phone, 'text' => null, 'reply_id' => 'xx']);
        $wa->expects($this->once())->method('sendLanguageList')->with($phone);

        $this->handleMessage($phone, '', 'm2', $wa);

        $this->assertSame('AWAIT_LANG', Conversation::where('wa_phone', $phone)->first()->step);
    }

    public function test_valid_language_moves_to_await_service_and_sends_buttons(): void
    {
        $phone = '393000000003';
        Conversation::create(['wa_phone' => $phone, 'step' => 'AWAIT_LANG', 'history' => []]);

        $wa = $this->createMock(WhatsAppClient::class);
        $wa->method('parseIncoming')->willReturn(['message_id' => 'm3', 'phone' => $phone, 'text' => null, 'reply_id' => 'en']);
        $wa->expects($this->once())->method('sendServiceButtons')->with($phone, 'en');

        $this->handleMessage($phone, '', 'm3', $wa);

        $convo = Conversation::where('wa_phone', $phone)->first();
        $this->assertSame('AWAIT_SERVICE', $convo->step);
        $this->assertSame('en', $convo->language);
    }

    public function test_valid_service_moves_to_in_service_and_runs_agent(): void
    {
        $phone = '393000000004';
        Conversation::create(['wa_phone' => $phone, 'step' => 'AWAIT_SERVICE', 'language' => 'en', 'history' => []]);

        $wa = $this->createMock(WhatsAppClient::class);
        $wa->method('parseIncoming')->willReturn(['message_id' => 'm4', 'phone' => $phone, 'text' => null, 'reply_id' => 'ticket']);
        $wa->method('sendText');

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->expects($this->once())
            ->method('handle')
            ->willReturn(['type' => 'text', 'text' => 'What is your full name?']);

        $this->handleMessage($phone, '', 'm4', $wa, $agent);

        $convo = Conversation::where('wa_phone', $phone)->first();
        $this->assertSame('IN_SERVICE', $convo->step);
        $this->assertSame('ticket', $convo->service);
    }

    public function test_done_conversation_resets_to_await_lang(): void
    {
        $phone = '393000000005';
        Conversation::create(['wa_phone' => $phone, 'step' => 'DONE', 'language' => 'en', 'service' => 'ticket', 'history' => []]);

        $wa = $this->createMock(WhatsAppClient::class);
        $wa->method('parseIncoming')->willReturn(['message_id' => 'm5', 'phone' => $phone, 'text' => 'hi again', 'reply_id' => null]);
        $wa->expects($this->once())->method('sendLanguageList')->with($phone);

        $this->handleMessage($phone, 'hi again', 'm5', $wa);

        $this->assertSame('AWAIT_LANG', Conversation::where('wa_phone', $phone)->first()->step);
    }

    public function test_reset_keyword_restarts_conversation_from_any_step(): void
    {
        $phone = '393000000006';
        Conversation::create(['wa_phone' => $phone, 'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => [['role' => 'user', 'content' => 'foo']]]);

        $wa = $this->createMock(WhatsAppClient::class);
        $wa->method('parseIncoming')->willReturn(['message_id' => 'm6', 'phone' => $phone, 'text' => 'menu', 'reply_id' => null]);
        $wa->expects($this->once())->method('sendLanguageList')->with($phone);

        $this->handleMessage($phone, 'menu', 'm6', $wa);

        $convo = Conversation::where('wa_phone', $phone)->first();
        $this->assertSame('AWAIT_LANG', $convo->step);
        $this->assertNull($convo->service);
    }

    public function test_completed_tool_call_notifies_staff_when_email_configured(): void
    {
        Notification::fake();
        Setting::set('staff_notification_email', 'staff@example.com');

        $phone = '393000000007';
        Conversation::create(['wa_phone' => $phone, 'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => []]);

        $wa = $this->createMock(WhatsAppClient::class);
        $wa->method('parseIncoming')->willReturn(['message_id' => 'm7', 'phone' => $phone, 'text' => 'confirm', 'reply_id' => null]);
        $wa->method('sendText');

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->method('handle')->willReturn([
            'type'  => 'tool',
            'name'  => 'submit_ticket_request',
            'input' => ['full_name' => 'John Doe'],
        ]);

        $this->handleMessage($phone, 'confirm', 'm7', $wa, $agent);

        $this->assertSame('DONE', Conversation::where('wa_phone', $phone)->first()->step);

        Notification::assertSentOnDemand(NewServiceRequestNotification::class);
    }

    public function test_completed_tool_call_skips_notification_when_no_email_configured(): void
    {
        Notification::fake();

        $phone = '393000000008';
        Conversation::create(['wa_phone' => $phone, 'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => []]);

        $wa = $this->createMock(WhatsAppClient::class);
        $wa->method('parseIncoming')->willReturn(['message_id' => 'm8', 'phone' => $phone, 'text' => 'confirm', 'reply_id' => null]);
        $wa->method('sendText');

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->method('handle')->willReturn([
            'type'  => 'tool',
            'name'  => 'submit_ticket_request',
            'input' => ['full_name' => 'Jane Doe'],
        ]);

        $this->handleMessage($phone, 'confirm', 'm8', $wa, $agent);

        Notification::assertNothingSent();
    }
}
