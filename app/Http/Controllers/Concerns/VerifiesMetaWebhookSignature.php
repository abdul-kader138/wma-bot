<?php

namespace App\Http\Controllers\Concerns;

use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Shared by WhatsAppWebhookController and MetaWebhookController (Messenger/Instagram) —
 * Meta signs each webhook POST with the app secret of whichever Meta App the sending
 * number/page/IG account belongs to. Most setups have every account for a platform under
 * one Meta App, so the platform-wide *_APP_SECRET in .env is enough. But an account can
 * live under a separate Meta App/Business account (its app_secret column, see the
 * 2026_08_10_130000 migration) — in that case its payloads are signed with a different
 * secret than the rest, so we accept a signature that matches *any* known secret for this
 * platform rather than assuming there's only one.
 */
trait VerifiesMetaWebhookSignature
{
    private function verifyWebhookSignature(Request $request, string $platform, ?string $globalSecret): void
    {
        $secrets = collect([$globalSecret])
            ->merge(
                WhatsAppAccount::active()
                    ->platform($platform)
                    ->whereNotNull('app_secret')
                    ->get()
                    ->pluck('app_secret')
            )
            ->filter()
            ->unique();

        if ($secrets->isEmpty()) {
            if (app()->environment('production')) {
                Log::warning(strtoupper($platform).'_APP_SECRET is not set; incoming webhook requests are not being verified.');
            }

            return;
        }

        $signature = $request->header('X-Hub-Signature-256', '');
        $body      = $request->getContent();

        $valid = $secrets->contains(
            fn (string $secret) => hash_equals('sha256='.hash_hmac('sha256', $body, $secret), $signature)
        );

        if (! $valid) {
            abort(403, 'Invalid signature');
        }
    }
}
