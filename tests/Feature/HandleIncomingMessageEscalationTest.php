<?php

namespace Tests\Feature;

use App\Jobs\HandleIncomingMessage;
use App\Models\BotAnswerEvent;
use App\Models\Conversation;
use App\Models\Faq;
use App\Models\Service;
use App\Models\WhatsAppAccount;
use App\Notifications\ConversationEscalatedNotification;
use App\Services\ClaudeAgent;
use App\Services\FaqMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HandleIncomingMessageEscalationTest extends TestCase
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
            'slug' => 'ticket',
            'label' => ['en' => 'Ticket booking'],
            'prompt_label' => 'booking a travel ticket',
            'color' => 'primary',
            'is_active' => true,
            'sort_order' => 0,
            'tool_name' => 'submit_ticket_request',
            'tool_description' => 'Save a completed ticket booking request.',
            'tool_fields' => [
                ['name' => 'full_name', 'type' => 'string', 'required' => true, 'description' => "Customer's full name"],
            ],
        ]);
    }

    private function makeTextValue(string $from, string $text, string $messageId = 'msg1'): array
    {
        return ['messages' => [['id' => $messageId, 'from' => $from, 'type' => 'text', 'text' => ['body' => $text]]]];
    }

    private function makeButtonValue(string $from, string $replyId, string $messageId): array
    {
        return ['messages' => [['id' => $messageId, 'from' => $from, 'type' => 'interactive', 'interactive' => ['button_reply' => ['id' => $replyId]]]]];
    }

    public function test_typed_escalation_keyword_pauses_bot_and_notifies_staff(): void
    {
        Notification::fake();

        \App\Models\Setting::set('staff_notification_email', 'staff@example.com');

        $phone = '393000000001';
        Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone,
            'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => [],
        ]);

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->expects($this->never())->method('handle');

        $job = new HandleIncomingMessage($this->makeTextValue($phone, 'talk to a human', 'e1'), $this->account->id);
        $job->handle($agent, app(FaqMatcher::class));

        $convo = Conversation::where('wa_phone', $phone)->first();
        $this->assertSame('HUMAN', $convo->step);
        $this->assertSame('pending', $convo->escalation_status);
        $this->assertNotNull($convo->escalated_at);

        Notification::assertSentOnDemand(ConversationEscalatedNotification::class);
    }

    public function test_human_button_tap_also_escalates(): void
    {
        $phone = '393000000002';
        Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone,
            'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => [],
        ]);

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->expects($this->never())->method('handle');

        $job = new HandleIncomingMessage($this->makeButtonValue($phone, 'human', 'e2'), $this->account->id);
        $job->handle($agent, app(FaqMatcher::class));

        $this->assertSame('HUMAN', Conversation::where('wa_phone', $phone)->first()->step);
    }

    public function test_escalated_conversation_stays_silent_and_only_logs_transcript(): void
    {
        $phone = '393000000003';
        Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone,
            'step' => 'HUMAN', 'language' => 'en', 'service' => 'ticket', 'history' => [],
            'escalated_at' => now(), 'escalation_status' => 'pending',
        ]);

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->expects($this->never())->method('handle');

        $job = new HandleIncomingMessage($this->makeTextValue($phone, 'hello, anyone there?', 'e3'), $this->account->id);
        $job->handle($agent, app(FaqMatcher::class));

        Http::assertNothingSent();

        $convo = Conversation::where('wa_phone', $phone)->first();
        $this->assertSame('HUMAN', $convo->step);
        $this->assertSame('hello, anyone there?', $convo->history[0]['content']);
    }

    public function test_reset_keyword_does_not_clear_an_escalated_conversation(): void
    {
        $phone = '393000000004';
        Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone,
            'step' => 'HUMAN', 'language' => 'en', 'service' => 'ticket', 'history' => [],
            'escalated_at' => now(), 'escalation_status' => 'pending',
        ]);

        $job = new HandleIncomingMessage($this->makeTextValue($phone, 'hi', 'e4'), $this->account->id);
        $job->handle($this->createMock(ClaudeAgent::class), app(FaqMatcher::class));

        $this->assertSame('HUMAN', Conversation::where('wa_phone', $phone)->first()->step);
    }

    public function test_thumbs_up_rates_latest_unrated_event_without_touching_history_or_budget(): void
    {
        $phone = '393000000005';
        $convo = Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone,
            'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket',
            'history' => [['role' => 'user', 'content' => 'price?'], ['role' => 'assistant', 'content' => 'It costs €50.']],
            'claude_message_count' => 3,
        ]);

        $event = BotAnswerEvent::create([
            'whatsapp_account_id' => $this->account->id,
            'conversation_id' => $convo->id,
            'wa_phone' => $phone,
            'platform' => 'whatsapp',
            'service' => 'ticket',
            'language' => 'en',
            'question' => 'price?',
            'answer' => 'It costs €50.',
            'match_source' => 'faq',
        ]);

        $job = new HandleIncomingMessage($this->makeButtonValue($phone, 'rate_up', 'e5'), $this->account->id);
        $job->handle($this->createMock(ClaudeAgent::class), app(FaqMatcher::class));

        $event->refresh();
        $this->assertSame(1, $event->rating);
        $this->assertNotNull($event->rated_at);

        $convo->refresh();
        $this->assertSame(3, $convo->claude_message_count);
        $this->assertCount(2, $convo->history);
    }

    public function test_faq_answer_logs_a_bot_answer_event(): void
    {
        $phone = '393000000006';
        Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone,
            'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => [],
        ]);

        Faq::create([
            'whatsapp_account_id' => $this->account->id, 'service' => null,
            'question' => ['en' => 'What is the price?'], 'keywords' => ['price'],
            'answer' => ['en' => 'It costs €50.'], 'is_active' => true,
        ]);

        $job = new HandleIncomingMessage($this->makeTextValue($phone, 'what is the price?', 'e6'), $this->account->id);
        $job->handle($this->createMock(ClaudeAgent::class), app(FaqMatcher::class));

        $event = BotAnswerEvent::where('wa_phone', $phone)->first();
        $this->assertNotNull($event);
        $this->assertSame('faq', $event->match_source);
        $this->assertSame('what is the price?', $event->question);
    }

    public function test_claude_text_reply_logs_a_bot_answer_event_with_none_match_source(): void
    {
        $phone = '393000000007';
        Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone,
            'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket',
            'history' => [['role' => 'user', 'content' => 'hi'], ['role' => 'assistant', 'content' => 'hi there']],
        ]);

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->expects($this->once())->method('handle')->willReturn([
            'type' => 'text', 'text' => 'Sure, what is your travel date?', 'chunk_count' => 0, 'top_distance' => null,
        ]);

        $job = new HandleIncomingMessage($this->makeTextValue($phone, 'when can I travel?', 'e7'), $this->account->id);
        $job->handle($agent, app(FaqMatcher::class));

        $event = BotAnswerEvent::where('wa_phone', $phone)->first();
        $this->assertNotNull($event);
        $this->assertSame('none', $event->match_source);
        $this->assertSame('when can I travel?', $event->question);
    }
}
