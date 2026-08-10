<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\VerifiesMetaWebhookSignature;
use App\Jobs\HandleIncomingMessage;
use App\Models\Setting;
use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Shared verify/signature/dispatch logic for Messenger and Instagram webhooks — both
 * use Meta's standard hub-challenge handshake and an identical entry[].messaging[]
 * payload shape, unlike WhatsApp's own webhook shape (see WhatsAppWebhookController,
 * which is intentionally not merged into this hierarchy since its payload differs).
 */
abstract class MetaWebhookController extends Controller
{
    use VerifiesMetaWebhookSignature;

    abstract protected function platform(): string;

    abstract protected function verifyTokenSettingKey(): string;

    abstract protected function configKey(): string;

    public function verify(Request $request)
    {
        $verifyToken = Setting::get($this->verifyTokenSettingKey()) ?: config("services.{$this->configKey()}.verify_token");
        $mode        = $request->query('hub_mode') ?: $request->query('hub.mode');
        $token       = $request->query('hub_verify_token') ?: $request->query('hub.verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($request->query('hub_challenge'), 200);
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        $this->verifyWebhookSignature($request, $this->platform(), config("services.{$this->configKey()}.app_secret"));

        foreach ($request->input('entry', []) as $entry) {
            $pageId  = $entry['id'] ?? null;
            $account = $pageId
                ? WhatsAppAccount::active()->platform($this->platform())->where('external_id', $pageId)->first()
                : null;

            if (! $account) {
                Log::warning("Unrecognized {$this->platform()} page/account id in webhook payload", [
                    'external_id' => $pageId,
                ]);

                continue;
            }

            foreach ($entry['messaging'] ?? [] as $event) {
                HandleIncomingMessage::dispatch(['messaging' => [$event]], $account->id, $this->platform());
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
