<?php

namespace App\Services\Maria\Google;

use App\Models\ConnectorAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleOAuthService
{
    public function __construct(private readonly GoogleConfiguration $configuration) {}

    public function authorizationRedirect(bool $includeWriteScopes = false): RedirectResponse
    {
        $this->assertConfigured();
        $state = Str::random(64);
        session()->put('google_oauth_state', $state);
        session()->put('google_oauth_write_consent', $includeWriteScopes);

        $scopes = config('services.google.scopes', []);
        if ($includeWriteScopes) {
            $scopes = array_values(array_unique([...$scopes, ...config('services.google.write_scopes', [])]));
        }

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $this->configuration->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $state,
        ]));
    }

    public function writeAuthorizationRedirect(): RedirectResponse
    {
        return $this->authorizationRedirect(true);
    }

    public function connect(User $user, string $code, string $state): ConnectorAccount
    {
        if (! hash_equals((string) session()->pull('google_oauth_state'), $state)) {
            throw new RuntimeException('Invalid Google OAuth state.');
        }
        $writeConsent = (bool) session()->pull('google_oauth_write_consent', false);

        $token = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $this->configuration->clientId(),
            'client_secret' => $this->configuration->clientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ])->throw()->json();

        $identity = Http::withToken($token['access_token'])
            ->get('https://openidconnect.googleapis.com/v1/userinfo')->throw()->json();
        $existing = ConnectorAccount::where('user_id', $user->id)->where('provider', 'google')
            ->where('provider_account_id', $identity['sub'])->first();

        return ConnectorAccount::updateOrCreate(
            ['user_id' => $user->id, 'provider' => 'google', 'provider_account_id' => $identity['sub']],
            [
                'email' => $identity['email'] ?? null,
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? $existing?->refresh_token,
                'scopes' => explode(' ', $token['scope'] ?? implode(' ', $writeConsent
                    ? [...config('services.google.scopes', []), ...config('services.google.write_scopes', [])]
                    : config('services.google.scopes', []))),
                'token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
                'status' => 'active', 'last_error' => null,
            ],
        );
    }

    public function disconnect(ConnectorAccount $connector): void
    {
        if ($connector->access_token) {
            Http::asForm()->post('https://oauth2.googleapis.com/revoke', ['token' => $connector->access_token]);
        }
        $connector->delete();
    }

    private function redirectUri(): string
    {
        return $this->configuration->redirectUri() ?: route('panel-api.connectors.google.callback');
    }

    private function assertConfigured(): void
    {
        if (! $this->configuration->clientId() || ! $this->configuration->clientSecret()) {
            throw new RuntimeException('Google OAuth credentials are not configured.');
        }
    }
}
