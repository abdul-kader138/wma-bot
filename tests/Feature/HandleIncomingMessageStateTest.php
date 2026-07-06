<?php

namespace Tests\Feature;

use App\Jobs\HandleIncomingMessage;
use App\Models\Conversation;
use App\Models\Setting;
use App\Models\WhatsAppAccount;
use App\Notifications\NewServiceRequestNotification;
use App\Services\ClaudeAgent;
use App\Services\FaqMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HandleIncomingMessageStateTest extends TestCase
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
    }

    private function makeTextValue(string $from, string $text, string $messageId = 'msg1'): array
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

    private function makeReplyValue(string $from, string $replyId, string $messageId, string $kind = 'list'): array
    {
        $key = $kind === 'list' ? 'list_reply' : 'button_reply';

        return [
            'messages' => [[
                'id'          => $messageId,
                'from'        => $from,
                'type'        => 'interactive',
                'interactive' => [$key => ['id' => $replyId]],
            ]],
        ];
    }

    private function handleMessage(array $value, ?ClaudeAgent $agent = null): void
    {
        $agent ??= $this->createMock(ClaudeAgent::class);

        $job = new HandleIncomingMessage($value, $this->account->id);
        $job->handle($agent, app(FaqMatcher::class));
    }

    private function assertSentInteractive(string $type, string $phone): void
    {
        Http::assertSent(function ($request) use ($type, $phone) {
            $payload = $request->data();

            return $request->url() === "https://graph.facebook.com/v22.0/test-phone-id/messages"
                && ($payload['to'] ?? null) === $phone
                && ($payload['type'] ?? null) === 'interactive'
                && ($payload['interactive']['type'] ?? null) === $type;
        });
    }

    private function assertSentText(string $phone, ?string $body = null): void
    {
        Http::assertSent(function ($request) use ($phone, $body) {
            $payload = $request->data();

            return ($payload['to'] ?? null) === $phone
                && ($payload['type'] ?? null) === 'text'
                && ($body === null || ($payload['text']['body'] ?? null) === $body);
        });
    }

    public function test_new_conversation_receives_language_list_and_moves_to_await_lang(): void
    {
        $phone = '393000000001';

        $this->handleMessage($this->makeTextValue($phone, 'hi', 'm1'));

        $this->assertSentInteractive('list', $phone);
        $this->assertSame('AWAIT_LANG', Conversation::where('wa_phone', $phone)->first()->step);
    }

    public function test_invalid_language_reply_resends_language_list_and_stays_await_lang(): void
    {
        $phone = '393000000002';
        Conversation::create(['whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone, 'step' => 'AWAIT_LANG', 'history' => []]);

        $this->handleMessage($this->makeReplyValue($phone, 'xx', 'm2', 'list'));

        $this->assertSentInteractive('list', $phone);
        $this->assertSame('AWAIT_LANG', Conversation::where('wa_phone', $phone)->first()->step);
    }

    public function test_valid_language_moves_to_await_service_and_sends_buttons(): void
    {
        $phone = '393000000003';
        Conversation::create(['whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone, 'step' => 'AWAIT_LANG', 'history' => []]);

        $this->handleMessage($this->makeReplyValue($phone, 'en', 'm3', 'list'));

        $this->assertSentInteractive('button', $phone);

        $convo = Conversation::where('wa_phone', $phone)->first();
        $this->assertSame('AWAIT_SERVICE', $convo->step);
        $this->assertSame('en', $convo->language);
    }

    public function test_valid_service_moves_to_in_service_and_runs_agent(): void
    {
        $phone = '393000000004';
        Conversation::create(['whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone, 'step' => 'AWAIT_SERVICE', 'language' => 'en', 'history' => []]);

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->expects($this->once())
            ->method('handle')
            ->willReturn(['type' => 'text', 'text' => 'What is your full name?']);

        $this->handleMessage($this->makeReplyValue($phone, 'ticket', 'm4', 'button'), $agent);

        $convo = Conversation::where('wa_phone', $phone)->first();
        $this->assertSame('IN_SERVICE', $convo->step);
        $this->assertSame('ticket', $convo->service);
    }

    public function test_done_conversation_resets_to_await_lang(): void
    {
        $phone = '393000000005';
        Conversation::create(['whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone, 'step' => 'DONE', 'language' => 'en', 'service' => 'ticket', 'history' => []]);

        $this->handleMessage($this->makeTextValue($phone, 'hi again', 'm5'));

        $this->assertSentInteractive('list', $phone);
        $this->assertSame('AWAIT_LANG', Conversation::where('wa_phone', $phone)->first()->step);
    }

    public function test_reset_keyword_restarts_conversation_from_any_step(): void
    {
        $phone = '393000000006';
        Conversation::create(['whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone, 'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => [['role' => 'user', 'content' => 'foo']]]);

        $this->handleMessage($this->makeTextValue($phone, 'menu', 'm6'));

        $this->assertSentInteractive('list', $phone);

        $convo = Conversation::where('wa_phone', $phone)->first();
        $this->assertSame('AWAIT_LANG', $convo->step);
        $this->assertNull($convo->service);
    }

    public function test_completed_tool_call_notifies_staff_when_email_configured(): void
    {
        Notification::fake();
        Setting::set('staff_notification_email', 'staff@example.com');

        $phone = '393000000007';
        Conversation::create(['whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone, 'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => []]);

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->method('handle')->willReturn([
            'type'  => 'tool',
            'name'  => 'submit_ticket_request',
            'input' => ['full_name' => 'John Doe'],
        ]);

        $this->handleMessage($this->makeTextValue($phone, 'confirm', 'm7'), $agent);

        $this->assertSame('DONE', Conversation::where('wa_phone', $phone)->first()->step);

        Notification::assertSentOnDemand(NewServiceRequestNotification::class);
    }

    public function test_completed_tool_call_skips_notification_when_no_email_configured(): void
    {
        Notification::fake();

        $phone = '393000000008';
        Conversation::create(['whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone, 'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => []]);

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->method('handle')->willReturn([
            'type'  => 'tool',
            'name'  => 'submit_ticket_request',
            'input' => ['full_name' => 'Jane Doe'],
        ]);

        $this->handleMessage($this->makeTextValue($phone, 'confirm', 'm8'), $agent);

        Notification::assertNothingSent();
    }
}
