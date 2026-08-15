<?php

namespace App\Services\Maria\Google;

use App\Models\ConnectorAccount;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GoogleWorkspaceClient
{
    public function __construct(private readonly GoogleConfiguration $configuration) {}

    public function get(ConnectorAccount $connector, string $url, array $query = []): array
    {
        try {
            $response = $this->retrying()->withToken($this->accessToken($connector))->get($url, $query)->throw()->json();
            $connector->update(['last_synced_at' => now(), 'last_error' => null, 'status' => 'active']);

            return $response;
        } catch (Throwable $error) {
            $connector->update(['last_error' => mb_substr($error->getMessage(), 0, 2000), 'status' => $this->statusFor($error)]);
            throw $error;
        }
    }

    public function post(ConnectorAccount $connector, string $url, array $payload): array
    {
        try {
            $response = $this->retrying()->withToken($this->accessToken($connector))->post($url, $payload)->throw()->json();
            $connector->update(['last_synced_at' => now(), 'last_error' => null, 'status' => 'active']);

            return $response;
        } catch (Throwable $error) {
            $connector->update(['last_error' => mb_substr($error->getMessage(), 0, 2000), 'status' => $this->statusFor($error)]);
            throw $error;
        }
    }

    private function retrying(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::retry(3, 1000, function (Throwable $error) {
            return $error instanceof ConnectionException
                || ($error instanceof RequestException && in_array($error->response->status(), [429, 500, 502, 503, 504], true));
        });
    }

    /** A 401/invalid_grant means reconnection is required; anything else is treated as a transient/unknown error. */
    private function statusFor(Throwable $error): string
    {
        if ($error instanceof RequestException && $error->response->status() === 401) {
            return 'needs_reauth';
        }

        return 'error';
    }

    private function accessToken(ConnectorAccount $connector): string
    {
        if ($connector->token_expires_at?->isAfter(now()->addMinute()) && $connector->access_token) {
            return $connector->access_token;
        }

        return Cache::lock("google-token-refresh:{$connector->id}", 20)->block(5, function () use ($connector) {
            $connector->refresh();
            if ($connector->token_expires_at?->isAfter(now()->addMinute()) && $connector->access_token) {
                return $connector->access_token;
            }
            if (! $connector->refresh_token) {
                throw new RuntimeException('Google connection must be re-authorized.');
            }
            $token = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $this->configuration->clientId(),
                'client_secret' => $this->configuration->clientSecret(),
                'refresh_token' => $connector->refresh_token,
                'grant_type' => 'refresh_token',
            ])->throw()->json();
            $connector->update([
                'access_token' => $token['access_token'],
                'token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
                'status' => 'active', 'last_error' => null,
            ]);

            return $token['access_token'];
        });
    }
}
