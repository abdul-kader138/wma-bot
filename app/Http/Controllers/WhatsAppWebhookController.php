<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\VerifiesMetaWebhookSignature;
use App\Jobs\HandleIncomingMessage;
use App\Models\Setting;
use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    use VerifiesMetaWebhookSignature;

    public function verify(Request $request)
    {
        $verifyToken = Setting::get('whatsapp_verify_token') ?: config('services.whatsapp.verify_token');
        $mode = $request->query('hub_mode') ?: $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?: $request->query('hub.verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($request->query('hub_challenge'), 200);
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        $this->verifyWebhookSignature($request, 'whatsapp', config('services.whatsapp.app_secret'));

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
}
