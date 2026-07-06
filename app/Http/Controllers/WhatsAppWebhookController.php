<?php

namespace App\Http\Controllers;

use App\Jobs\HandleIncomingMessage;
use App\Models\Setting;
use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $verifyToken = Setting::get('whatsapp_verify_token') ?: config('services.whatsapp.verify_token');

        if ($request->query('hub_mode') === 'subscribe'
            && $request->query('hub_verify_token') === $verifyToken) {
            return response($request->query('hub_challenge'), 200);
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        $this->verifySignature($request);

        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? null;
                if (! $value || empty($value['messages'])) {
                    continue;
                }

                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
                $account       = $phoneNumberId
                    ? WhatsAppAccount::active()->where('phone_number_id', $phoneNumberId)->first()
                    : null;

                if (! $account) {
                    Log::warning('Unrecognized WhatsApp phone_number_id in webhook payload', [
                        'phone_number_id' => $phoneNumberId,
                    ]);
                    continue;
                }

                HandleIncomingMessage::dispatch($value, $account->id);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function verifySignature(Request $request): void
    {
        $secret = config('services.whatsapp.app_secret');
        if (! $secret) {
            if (app()->environment('production')) {
                Log::warning('WHATSAPP_APP_SECRET is not set; incoming webhook requests are not being verified.');
            }

            return;
        }

        $signature = $request->header('X-Hub-Signature-256', '');
        $expected  = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            abort(403, 'Invalid signature');
        }
    }
}
