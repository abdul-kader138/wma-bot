<?php

namespace Tests\Feature;

use App\Jobs\HandleIncomingMessage;
use App\Models\CatalogueEntry;
use App\Models\Conversation;
use App\Models\Service;
use App\Models\WhatsAppAccount;
use App\Services\ClaudeAgent;
use App\Services\FaqMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HandleIncomingMessageCatalogueTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppAccount $account;

    private Service $service;

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

        $this->service = Service::create([
            'whatsapp_account_id' => $this->account->id,
            'slug'             => 'food',
            'label'            => ['en' => 'Order food', 'it' => 'Ordina', 'bn' => 'খাবার অর্ডার'],
            'prompt_label'     => 'ordering food',
            'color'            => 'primary',
            'is_active'        => true,
            'sort_order'       => 0,
            'tool_name'        => 'submit_food_order',
            'tool_description' => 'Save a completed food order.',
            'tool_fields'      => [
                ['name' => 'full_name', 'type' => 'string', 'required' => true, 'description' => "Customer's full name"],
            ],
            'catalogue_enabled' => true,
        ]);

        CatalogueEntry::create([
            'whatsapp_account_id' => $this->account->id,
            'service_id'       => $this->service->id,
            'name'             => ['en' => 'Margherita Pizza'],
            'price_minor_units' => 1250,
            'currency'         => 'EUR',
            'is_active'        => true,
            'sort_order'       => 0,
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

    private function makeReplyValue(string $from, string $replyId, string $messageId): array
    {
        return [
            'messages' => [[
                'id'          => $messageId,
                'from'        => $from,
                'type'        => 'interactive',
                'interactive' => ['list_reply' => ['id' => $replyId]],
            ]],
        ];
    }

    private function handleMessage(array $value): void
    {
        $job = new HandleIncomingMessage($value, $this->account->id);
        $job->handle($this->createMock(ClaudeAgent::class), app(FaqMatcher::class));
    }

    // Regression test for the catalogue/reset keyword conflict (F1): typing "menu"
    // in a catalogue-enabled service must show the catalogue, not wipe the
    // conversation back to language selection the way the generic reset-keyword
    // list would if checked first.
    public function test_menu_keyword_in_catalogue_enabled_service_shows_catalogue_without_resetting(): void
    {
        $phone = '393000000010';
        Conversation::create([
            'whatsapp_account_id' => $this->account->id,
            'wa_phone' => $phone,
            'step' => 'IN_SERVICE',
            'language' => 'en',
            'service' => 'food',
            'history' => [['role' => 'user', 'content' => 'hi']],
        ]);

        $this->handleMessage($this->makeTextValue($phone, 'menu', 'm1'));

        Http::assertSent(function ($request) use ($phone) {
            $payload = $request->data();

            return ($payload['to'] ?? null) === $phone
                && str_starts_with($payload['interactive']['action']['sections'][0]['rows'][0]['title'] ?? '', 'Margherita Pizza');
        });

        $convo = Conversation::where('wa_phone', $phone)->first();
        $this->assertSame('IN_SERVICE', $convo->step);
        $this->assertSame('food', $convo->service);
        $this->assertNotEmpty($convo->history);
    }

    public function test_menu_keyword_still_resets_a_non_catalogue_service(): void
    {
        $phone = '393000000011';
        Service::where('slug', 'food')->update(['catalogue_enabled' => false]);

        Conversation::create([
            'whatsapp_account_id' => $this->account->id,
            'wa_phone' => $phone,
            'step' => 'IN_SERVICE',
            'language' => 'en',
            'service' => 'food',
            'history' => [['role' => 'user', 'content' => 'hi']],
        ]);

        $this->handleMessage($this->makeTextValue($phone, 'menu', 'm2'));

        $convo = Conversation::where('wa_phone', $phone)->first();
        $this->assertSame('AWAIT_LANG', $convo->step);
        $this->assertNull($convo->service);
    }

    public function test_tapping_a_catalogue_entry_returns_its_details(): void
    {
        $phone = '393000000012';
        $entry = CatalogueEntry::first();
        Conversation::create([
            'whatsapp_account_id' => $this->account->id,
            'wa_phone' => $phone,
            'step' => 'IN_SERVICE',
            'language' => 'en',
            'service' => 'food',
            'history' => [],
        ]);

        $this->handleMessage($this->makeReplyValue($phone, "cat_{$entry->id}", 'm3'));

        Http::assertSent(fn ($request) => str_starts_with(
            $request->data()['interactive']['body']['text'] ?? '',
            'Margherita Pizza — 12.50 EUR'
        ));
    }

    public function test_stale_catalogue_entry_id_fails_safely(): void
    {
        $phone = '393000000013';
        Conversation::create([
            'whatsapp_account_id' => $this->account->id,
            'wa_phone' => $phone,
            'step' => 'IN_SERVICE',
            'language' => 'en',
            'service' => 'food',
            'history' => [],
        ]);

        $this->handleMessage($this->makeReplyValue($phone, 'cat_999999', 'm4'));

        Http::assertSent(fn ($request) => str_contains($request->data()['text']['body'] ?? '', 'no longer available'));
    }
}
