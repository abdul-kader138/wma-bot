<?php

namespace App\Services\Maria\Concerns;

use App\Models\Approval;
use App\Models\AssistantAlert;
use App\Models\AssistantBrief;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\MariaBriefReadyNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Emails a brief digest to the owner when `maria_digest_email_enabled` is on.
 * Call after the brief row is committed. A send failure is logged, never fatal —
 * the brief itself is what matters.
 */
trait SendsBriefDigest
{
    protected function sendBriefDigest(AssistantBrief $brief, User $owner): void
    {
        if (! filter_var(Setting::get('maria_digest_email_enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        if (blank($owner->email)) {
            return;
        }

        try {
            $owner->notify(new MariaBriefReadyNotification(
                $brief,
                Approval::where('user_id', $owner->id)->where('decision', 'pending')->where('expires_at', '>', now())->count(),
                AssistantAlert::where('user_id', $owner->id)->where('status', 'active')->count(),
            ));
        } catch (Throwable $e) {
            Log::warning('Failed to send Maria brief digest', ['user_id' => $owner->id, 'error' => $e->getMessage()]);
        }
    }
}
