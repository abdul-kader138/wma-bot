<?php

namespace Tests\Feature;

use App\Models\ConnectorAccount;
use App\Models\User;
use App\Services\Maria\Google\GmailReadClient;
use App\Services\Maria\Google\GoogleOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleWorkspaceConnectorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.google.client_id' => 'google-client',
            'services.google.client_secret' => 'google-secret',
            'services.google.redirect_uri' => 'https://example.test/panel-api/connectors/google/callback',
        ]);
    }

    public function test_authorization_redirect_requests_only_configured_read_scopes_and_state(): void
    {
        $response = app(GoogleOAuthService::class)->authorizationRedirect();
        parse_str(parse_url($response->getTargetUrl(), PHP_URL_QUERY), $query);

        $this->assertSame('code', $query['response_type']);
        $this->assertSame('offline', $query['access_type']);
        $this->assertNotEmpty($query['state']);
        $this->assertStringContainsString('gmail.readonly', $query['scope']);
        $this->assertStringContainsString('calendar.events.readonly', $query['scope']);
        $this->assertStringContainsString('drive.metadata.readonly', $query['scope']);
        $this->assertStringNotContainsString('gmail.send', $query['scope']);
    }

    public function test_callback_exchange_stores_encrypted_google_connection(): void
    {
        $user = User::factory()->create();
        $redirect = app(GoogleOAuthService::class)->authorizationRedirect();
        parse_str(parse_url($redirect->getTargetUrl(), PHP_URL_QUERY), $query);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-1', 'refresh_token' => 'refresh-1',
                'expires_in' => 3600, 'scope' => 'openid email',
            ]),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-user-1', 'email' => 'owner@example.com',
            ]),
        ]);

        $connector = app(GoogleOAuthService::class)->connect($user, 'auth-code', $query['state']);

        $this->assertSame('owner@example.com', $connector->email);
        $this->assertSame('access-1', $connector->access_token);
        $this->assertNotSame('access-1', \DB::table('connector_accounts')->value('access_token'));
    }

    public function test_write_authorization_is_a_separate_explicit_consent(): void
    {
        $response = app(GoogleOAuthService::class)->writeAuthorizationRedirect();
        parse_str(parse_url($response->getTargetUrl(), PHP_URL_QUERY), $query);

        $this->assertStringContainsString('gmail.send', $query['scope']);
        $this->assertStringContainsString('/auth/calendar.events', $query['scope']);
        $this->assertStringContainsString('gmail.readonly', $query['scope']);
    }

    public function test_expired_token_is_refreshed_before_read_request(): void
    {
        $user = User::factory()->create();
        $connector = ConnectorAccount::create([
            'user_id' => $user->id, 'provider' => 'google', 'provider_account_id' => 'g1',
            'access_token' => 'expired', 'refresh_token' => 'refresh',
            'token_expires_at' => now()->subMinute(), 'status' => 'active',
        ]);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'fresh-token', 'expires_in' => 3600]),
            'https://gmail.googleapis.com/*' => Http::response(['messages' => [['id' => 'm1']]]),
        ]);

        $result = app(GmailReadClient::class)->listMessages($connector);

        $this->assertSame('m1', $result['messages'][0]['id']);
        $this->assertSame('fresh-token', $connector->fresh()->access_token);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'gmail.googleapis.com')
            && $request->hasHeader('Authorization', 'Bearer fresh-token'));
    }

    public function test_invalid_oauth_state_is_rejected_before_token_exchange(): void
    {
        Http::fake();
        $this->expectException(\RuntimeException::class);

        app(GoogleOAuthService::class)->connect(User::factory()->create(), 'code', 'wrong-state');
    }

    public function test_transient_server_error_is_retried_and_succeeds(): void
    {
        $user = User::factory()->create();
        $connector = ConnectorAccount::create([
            'user_id' => $user->id, 'provider' => 'google', 'provider_account_id' => 'g1',
            'access_token' => 'token', 'refresh_token' => 'refresh',
            'token_expires_at' => now()->addHour(), 'status' => 'active',
        ]);
        Http::fake(['https://gmail.googleapis.com/*' => Http::sequence()
            ->push(['error' => 'temporarily unavailable'], 503)
            ->push(['messages' => [['id' => 'm1']]])]);

        $result = app(GmailReadClient::class)->listMessages($connector);

        $this->assertSame('m1', $result['messages'][0]['id']);
        $this->assertSame('active', $connector->fresh()->status);
        Http::assertSentCount(2);
    }

    public function test_unauthorized_response_marks_connector_needs_reauth(): void
    {
        $user = User::factory()->create();
        $connector = ConnectorAccount::create([
            'user_id' => $user->id, 'provider' => 'google', 'provider_account_id' => 'g1',
            'access_token' => 'token', 'refresh_token' => 'refresh',
            'token_expires_at' => now()->addHour(), 'status' => 'active',
        ]);
        Http::fake(['https://gmail.googleapis.com/*' => Http::response(['error' => 'invalid_grant'], 401)]);

        try {
            app(GmailReadClient::class)->listMessages($connector);
            $this->fail('Expected a request exception.');
        } catch (\Illuminate\Http\Client\RequestException) {
            // expected
        }

        $this->assertSame('needs_reauth', $connector->fresh()->status);
    }
}
