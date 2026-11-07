<?php

namespace App\Jobs;

use App\Contracts\MessagingChannel;
use App\Models\Conversation;
use App\Models\Service;
use App\Models\Setting;
use App\Models\WhatsAppAccount;
use App\Services\InstagramClient;
use App\Services\MessengerClient;
use App\Services\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Nudges customers who went quiet mid-service: "still need help with X?" after a few
 * hours of silence. Scheduled every 15 minutes (routes/console.php). One nudge per
 * quiet spell — idle_nudged_at only resets once the customer messages again (bumping
 * last_customer_message_at past it), and nothing is sent once WhatsApp's 24h
 * customer-service window has already closed, since the API would just reject it.
 */
class SendIdleFollowUps implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 600;

    public function handle(): void
    {
        $hours = (int) Setting::get('bot_idle_follow_up_hours', 0);
        if ($hours <= 0) {
            return;
        }

        Conversation::query()
            ->where('step', 'IN_SERVICE')
            ->whereNull('opted_out_at')
            ->whereNotNull('last_customer_message_at')
            ->where('last_customer_message_at', '<', now()->subHours($hours))
            // WhatsApp's 24h customer-service window: beyond this, a send would just
            // be rejected by the API, so don't bother trying.
            ->where('last_customer_message_at', '>', now()->subHours(24))
            ->where(function ($query) {
                $query->whereNull('idle_nudged_at')->orWhereColumn('idle_nudged_at', '<', 'last_customer_message_at');
            })
            ->with('whatsAppAccount')
            ->chunkById(100, function ($conversations) {
                foreach ($conversations as $convo) {
                    $this->nudge($convo);
                }
            });
    }

    private function nudge(Conversation $convo): void
    {
        $account = $convo->whatsAppAccount;
        if (! $account || ! $account->is_active) {
            return;
        }

        $message = $this->nudgeMessage($convo, $account);
        if (! $message) {
            return;
        }

        try {
            $this->resolveChannel($account, $convo->platform)->sendText($convo->wa_phone, $message);
            $convo->update(['idle_nudged_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Idle follow-up send failed', ['conversation_id' => $convo->id, 'error' => $e->getMessage()]);
        }
    }

    private function nudgeMessage(Conversation $convo, WhatsAppAccount $account): ?string
    {
        $lang = $convo->language ?? 'en';
        $messages = Setting::get('bot_idle_follow_up_message', []);
        $message = $messages[$lang] ?? $messages['en'] ?? null;

        if (! $message) {
            return null;
        }

        $serviceLabel = $convo->service
            ? (Service::toConfig($account->id)[$convo->service]['label'][$lang] ?? null)
            : null;

        return $serviceLabel ? "{$message} ({$serviceLabel})" : $message;
    }

    private function resolveChannel(WhatsAppAccount $account, string $platform): MessagingChannel
    {
        return match ($platform) {
            'messenger' => new MessengerClient($account),
            'instagram' => new InstagramClient($account),
            default => new WhatsAppClient($account),
        };
    }
}
