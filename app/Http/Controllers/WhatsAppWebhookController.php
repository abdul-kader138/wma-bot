<?php

namespace App\Http\Controllers;

use App\Jobs\HandleIncomingMessage;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $verifyToken = config('services.whatsapp.verify_token');

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
                if ($value && ! empty($value['messages'])) {
                    HandleIncomingMessage::dispatch($value);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function verifySignature(Request $request): void
    {
        $secret = config('services.whatsapp.app_secret');
        if (! $secret) {
            return;
        }

        $signature = $request->header('X-Hub-Signature-256', '');
        $expected  = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            abort(403, 'Invalid signature');
        }
    }
}
