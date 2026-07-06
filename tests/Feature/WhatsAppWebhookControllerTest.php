<?php

namespace Tests\Feature;

use App\Jobs\HandleIncomingMessage;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WhatsAppWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makePayload(string $phoneNumberId, string $from, string $messageId): array
    {
        return [
            'entry' => [[
                'id' => 'waba-1',
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => $phoneNumberId],
                        'messages' => [[
                            'id'   => $messageId,
                            'from' => $from,
                            'type' => 'text',
                            'text' => ['body' => 'hi'],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    public function test_recognized_phone_number_id_dispatches_job_with_correct_account(): void
    {
        Bus::fake();

        $account = WhatsAppAccount::create([
            'name'            => 'Sales',
            'phone_number_id' => 'phone-abc',
            'access_token'    => 'token-abc',
            'api_version'     => 'v22.0',
            'is_active'       => true,
            'is_default'      => true,
        ]);

        $this->postJson('/api/webhook/whatsapp', $this->makePayload('phone-abc', '393000000001', 'm1'))
            ->assertOk();

        Bus::assertDispatched(HandleIncomingMessage::class, function (HandleIncomingMessage $job) use ($account) {
            return $job->whatsAppAccountId === $account->id;
        });
    }

    public function test_unrecognized_phone_number_id_is_skipped_and_logged(): void
    {
        Bus::fake();
        Log::spy();

        $this->postJson('/api/webhook/whatsapp', $this->makePayload('unknown-phone', '393000000002', 'm2'))
            ->assertOk();

        Bus::assertNotDispatched(HandleIncomingMessage::class);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message) => $message === 'Unrecognized WhatsApp phone_number_id in webhook payload')
            ->once();
    }

    public function test_inactive_account_phone_number_id_is_treated_as_unrecognized(): void
    {
        Bus::fake();

        WhatsAppAccount::create([
            'name'            => 'Disabled',
            'phone_number_id' => 'phone-disabled',
            'access_token'    => 'token-disabled',
            'api_version'     => 'v22.0',
            'is_active'       => false,
            'is_default'      => false,
        ]);

        $this->postJson('/api/webhook/whatsapp', $this->makePayload('phone-disabled', '393000000003', 'm3'))
            ->assertOk();

        Bus::assertNotDispatched(HandleIncomingMessage::class);
    }
}
