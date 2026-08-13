<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\Maria\Google\GoogleOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleSettingsConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_oauth_prefers_settings_and_encrypts_client_secret(): void
    {
        config(['services.google.client_id' => 'env-client', 'services.google.client_secret' => 'env-secret', 'services.google.redirect_uri' => 'https://env.test/callback']);
        Setting::set('google_client_id', 'settings-client', 'google');
        Setting::setSecret('google_client_secret', 'settings-secret', 'google');
        Setting::set('google_redirect_uri', 'https://settings.test/callback', 'google');

        $response = app(GoogleOAuthService::class)->authorizationRedirect();
        parse_str(parse_url($response->getTargetUrl(), PHP_URL_QUERY), $query);

        $this->assertSame('settings-client', $query['client_id']);
        $this->assertSame('https://settings.test/callback', $query['redirect_uri']);
        $this->assertSame('settings-secret', Setting::getSecret('google_client_secret'));
        $this->assertNotSame('settings-secret', Setting::where('key', 'google_client_secret')->value('value'));
    }

    public function test_environment_remains_the_google_configuration_fallback(): void
    {
        config(['services.google.client_id' => 'env-client', 'services.google.client_secret' => 'env-secret', 'services.google.redirect_uri' => 'https://env.test/callback']);

        $response = app(GoogleOAuthService::class)->authorizationRedirect();
        parse_str(parse_url($response->getTargetUrl(), PHP_URL_QUERY), $query);

        $this->assertSame('env-client', $query['client_id']);
        $this->assertSame('https://env.test/callback', $query['redirect_uri']);
    }
}
