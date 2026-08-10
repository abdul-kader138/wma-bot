<?php

namespace Tests\Feature;

use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookSignatureVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function sign(array $payload, string $secret): string
    {
        return 'sha256='.hash_hmac('sha256', json_encode($payload), $secret);
    }

    public function test_whatsapp_webhook_accepts_signature_from_global_secret(): void
    {
        config(['services.whatsapp.app_secret' => 'global-secret']);

        $payload = ['entry' => []];

        $this->postJson('/api/webhook/whatsapp', $payload, [
            'X-Hub-Signature-256' => $this->sign($payload, 'global-secret'),
        ])->assertOk();
    }

    public function test_whatsapp_webhook_rejects_signature_matching_neither_global_nor_account_secret(): void
    {
        config(['services.whatsapp.app_secret' => 'global-secret']);

        $payload = ['entry' => []];

        $this->postJson('/api/webhook/whatsapp', $payload, [
            'X-Hub-Signature-256' => $this->sign($payload, 'wrong-secret'),
        ])->assertForbidden();
    }

    public function test_whatsapp_webhook_accepts_signature_from_per_account_secret_override(): void
    {
        config(['services.whatsapp.app_secret' => 'global-secret']);

        WhatsAppAccount::create([
            'name'            => 'Separate Meta Business',
            'phone_number_id' => 'phone-xyz',
            'access_token'    => 'token-xyz',
            'app_secret'      => 'other-meta-app-secret',
            'api_version'     => 'v22.0',
            'is_active'       => true,
        ]);

        $payload = ['entry' => []];

        // Signed with the account's own secret, not the platform-wide one — this is
        // exactly what Meta sends for an account under a different Meta App.
        $this->postJson('/api/webhook/whatsapp', $payload, [
            'X-Hub-Signature-256' => $this->sign($payload, 'other-meta-app-secret'),
        ])->assertOk();
    }

    public function test_messenger_webhook_accepts_signature_from_per_account_secret_override(): void
    {
        config(['services.messenger.app_secret' => 'global-secret']);

        WhatsAppAccount::create([
            'name'         => 'Separate Meta Business Page',
            'platform'     => 'messenger',
            'external_id'  => 'page-999',
            'access_token' => 'token-999',
            'app_secret'   => 'other-meta-app-secret',
            'api_version'  => 'v22.0',
            'is_active'    => true,
        ]);

        $payload = ['object' => 'page', 'entry' => []];

        $this->postJson('/api/webhook/messenger', $payload, [
            'X-Hub-Signature-256' => $this->sign($payload, 'other-meta-app-secret'),
        ])->assertOk();
    }

    public function test_instagram_webhook_rejects_signature_from_an_inactive_accounts_secret(): void
    {
        config(['services.instagram.app_secret' => 'global-secret']);

        WhatsAppAccount::create([
            'name'         => 'Disabled IG',
            'platform'     => 'instagram',
            'external_id'  => 'ig-disabled',
            'access_token' => 'token-disabled',
            'app_secret'   => 'disabled-account-secret',
            'api_version'  => 'v22.0',
            'is_active'    => false,
        ]);

        $payload = ['object' => 'instagram', 'entry' => []];

        $this->postJson('/api/webhook/instagram', $payload, [
            'X-Hub-Signature-256' => $this->sign($payload, 'disabled-account-secret'),
        ])->assertForbidden();
    }
}
