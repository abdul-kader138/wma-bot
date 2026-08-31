<?php

namespace App\Services\Maria;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The single HTTP path for every Maria call to the Anthropic Messages API.
 * Centralises auth, timeout, transient retry, and — when configured — failover
 * to a secondary model so one bad model id or a provider blip on the primary
 * does not take the whole assistant down.
 */
class ClaudeClient
{
    private const URL = 'https://api.anthropic.com/v1/messages';

    /**
     * @param  array<string,mixed>  $payload  everything except `model`, which this method fills in
     * @return array<string,mixed>
     *
     * @throws RequestException|ConnectionException when every configured model fails
     */
    public function messages(array $payload): array
    {
        $models = array_values(array_unique(array_filter([
            Setting::get('claude_model') ?: config('services.anthropic.model'),
            Setting::get('claude_fallback_model') ?: null,
        ])));

        $lastError = null;
        foreach ($models as $index => $model) {
            try {
                return Http::withHeaders([
                    'x-api-key' => Setting::getSecret('claude_api_key') ?: config('services.anthropic.key'),
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                    ->timeout(40)
                    ->retry(2, 500, fn (Throwable $e) => $this->isTransient($e))
                    ->post(self::URL, [...$payload, 'model' => $model])
                    ->throw()
                    ->json();
            } catch (ConnectionException|RequestException $error) {
                $lastError = $error;
                $isLast = $index === count($models) - 1;

                if ($isLast || ! $this->shouldFailover($error)) {
                    throw $error;
                }

                Log::warning('Maria Claude call failed; failing over to the next model', [
                    'failed_model' => $model,
                    'status' => $error instanceof RequestException ? $error->response->status() : null,
                    'error' => $error->getMessage(),
                ]);
            }
        }

        throw $lastError ?? new ConnectionException('No Claude model configured.');
    }

    private function isTransient(Throwable $error): bool
    {
        return $error instanceof ConnectionException
            || ($error instanceof RequestException && in_array($error->response->status(), [429, 500, 502, 503, 529], true));
    }

    /** Worth trying the fallback model for: connectivity, provider errors, and "unknown model" 404s. */
    private function shouldFailover(Throwable $error): bool
    {
        if ($error instanceof ConnectionException) {
            return true;
        }

        return $error instanceof RequestException
            && in_array($error->response->status(), [404, 429, 500, 502, 503, 529], true);
    }
}
