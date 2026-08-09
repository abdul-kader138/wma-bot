<?php

namespace Tests\Feature;

use App\Jobs\HandleIncomingMessage;
use App\Models\Conversation;
use App\Models\Service;
use App\Models\Setting;
use App\Models\WhatsAppAccount;
use App\Services\ClaudeAgent;
use App\Services\FaqMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class HandleIncomingMessageRateLimitTest extends TestCase
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
            'slug' => 'ticket', 'label' => ['en' => 'Ticket booking'], 'prompt_label' => 'x',
            'color' => 'primary', 'is_active' => true, 'sort_order' => 0,
            'tool_name' => 'submit_ticket_request', 'tool_description' => 'x',
            'tool_fields' => [['name' => 'full_name', 'type' => 'string', 'required' => true, 'description' => 'x']],
        ]);

        Setting::set('claude_rate_limit_per_minute', 1, 'claude');
        // Exhaust the one available slot up front so the job under test is guaranteed
        // to be over budget, regardless of call order.
        RateLimiter::hit('claude-api-calls', 60);
    }

    private function makeTextValue(string $from, string $text, string $messageId): array
    {
        return [
            'messages' => [[
                'id' => $messageId, 'from' => $from, 'type' => 'text', 'text' => ['body' => $text],
            ]],
        ];
    }

    private function makeReplyValue(string $from, string $replyId, string $messageId): array
    {
        return [
            'messages' => [[
                'id' => $messageId, 'from' => $from, 'type' => 'interactive',
                'interactive' => ['button_reply' => ['id' => $replyId]],
            ]],
        ];
    }

    public function test_in_service_message_defers_claude_call_without_duplicating_history(): void
    {
        $phone = '393000000050';
        $convo = Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone,
            'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => [],
        ]);

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->expects($this->never())->method('handle');

        $job = new HandleIncomingMessage($this->makeTextValue($phone, 'My name is John', 'r1'), $this->account->id);
        $job->handle($agent, app(FaqMatcher::class));

        $convo->refresh();
        // Deferred before appendHistory() ran — nothing was logged for this attempt,
        // so a later retry won't see the customer's message doubled.
        $this->assertSame([], $convo->history);
        $this->assertSame('IN_SERVICE', $convo->step);
    }

    public function test_await_service_selection_defers_without_sending_welcome_or_advancing_step(): void
    {
        $phone = '393000000051';
        Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone,
            'step' => 'AWAIT_SERVICE', 'language' => 'en', 'history' => [],
        ]);

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->expects($this->never())->method('handle');

        $job = new HandleIncomingMessage($this->makeReplyValue($phone, 'ticket', 'r2'), $this->account->id);
        $job->handle($agent, app(FaqMatcher::class));

        Http::assertNothingSent();

        $convo = Conversation::where('wa_phone', $phone)->first();
        $this->assertSame('AWAIT_SERVICE', $convo->step);
        $this->assertNull($convo->service);
    }

    public function test_faq_match_is_not_affected_by_claude_rate_limit(): void
    {
        $phone = '393000000052';
        Conversation::create([
            'whatsapp_account_id' => $this->account->id, 'wa_phone' => $phone,
            'step' => 'IN_SERVICE', 'language' => 'en', 'service' => 'ticket', 'history' => [],
        ]);

        \App\Models\Faq::create([
            'whatsapp_account_id' => $this->account->id, 'service' => null,
            'question' => ['en' => 'What is the price?'], 'keywords' => ['price'],
            'answer' => ['en' => 'It costs €50.'], 'is_active' => true,
        ]);

        $agent = $this->createMock(ClaudeAgent::class);
        $agent->expects($this->never())->method('handle');

        $job = new HandleIncomingMessage($this->makeTextValue($phone, 'what is the price?', 'r3'), $this->account->id);
        $job->handle($agent, app(FaqMatcher::class));

        Http::assertSent(fn ($request) => ($request->data()['text']['body'] ?? null) === 'It costs €50.');
    }
}
