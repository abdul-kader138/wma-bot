<?php

namespace Tests\Feature;

use App\Jobs\HandleIncomingMessage;
use App\Models\Conversation;
use App\Models\Service;
use App\Models\Setting;
use App\Models\WhatsAppAccount;
use App\Notifications\NewServiceRequestNotification;
use App\Services\ClaudeAgent;
use App\Services\FaqMatcher;
use Illuminate\Contracts\Queue\Job as QueueJobContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

        Service::create([
            'whatsapp_account_id' => $this->account->id,
            'slug'             => 'ticket',
            'label'            => ['en' => 'Ticket booking', 'it' => 'Biglietti', 'bn' => 'টিকিট বুকিং'],
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

    public function test_first_attempt_is_deduplicated_by_message_id(): void
    {
        $phone = '393000000010';
        Cache::add('wa_msg:dupe1', 1, now()->addHours(24));

        // Same message id already "processed" on a prior delivery — a genuine
        // duplicate webhook redelivery on attempt 1 must be a no-op.
        $this->handleMessage($this->makeTextValue($phone, 'hi', 'dupe1'));

        $this->assertNull(Conversation::where('wa_phone', $phone)->first());
    }

    public function test_released_retry_is_not_swallowed_by_its_own_dedup_key(): void
    {
        $phone = '393000000011';
        // Mirrors what attempt 1 already did before releasing itself back onto the
        // queue (e.g. lock contention, or the Claude rate limiter): the dedup key
        // for this message is already cached.
        Cache::add('wa_msg:retry1', 1, now()->addHours(24));

        $queueJob = $this->createMock(QueueJobContract::class);
        $queueJob->method('attempts')->willReturn(2);

        $job = new HandleIncomingMessage($this->makeTextValue($phone, 'hi', 'retry1'), $this->account->id);
        $job->setJob($queueJob);
        $job->handle($this->createMock(ClaudeAgent::class), app(FaqMatcher::class));

        // Attempt 2 of the SAME job must still be processed — not dropped just
        // because its own attempt 1 already set the dedup key.
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

        // Services are sent as a 'list' (not 'button') so more than 3 options fit —
        // see WhatsAppClient::sendServiceButtons().
        $this->assertSentInteractive('list', $phone);

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

    public function test_service_selection_sends_language_specific_welcome_message(): void
    {
        Setting::set('bot_welcome_message', [
            'en' => 'Welcome EN',
            'it' => 'Welcome IT',
            'bn' => 'Welcome BN',
        ], 'bot');

        $phone = '393000000009';
        Conversation::create(['whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone, 'step' => 'AWAIT_SERVICE', 'language' => 'bn', 'history' => []]);

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->expects($this->once())
            ->method('handle')
            ->willReturn(['type' => 'text', 'text' => 'first question']);

        $this->handleMessage($this->makeReplyValue($phone, 'ticket', 'm9', 'button'), $agent);

        // Sent to the customer in their selected language.
        $this->assertSentText($phone, 'Welcome BN');

        // Also kept in the transcript history so it's visible in the admin panel.
        $convo = Conversation::where('wa_phone', $phone)->first();
        $this->assertSame('assistant', $convo->history[0]['role']);
        $this->assertSame('Welcome BN', $convo->history[0]['content']);
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
