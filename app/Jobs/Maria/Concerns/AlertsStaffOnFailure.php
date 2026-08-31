<?php

namespace App\Jobs\Maria\Concerns;

use App\Models\Setting;
use App\Notifications\MariaWorkflowFailedNotification;
use App\Services\Maria\ClaudeRateLimited;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Shared `failed()` hook for Maria workflow jobs: when a job gives up after all
 * retries, email the configured staff address once so a silently stuck workflow
 * is visible. Transient rate-limit exhaustion is logged, not emailed — it clears
 * on its own and would only be noise.
 */
trait AlertsStaffOnFailure
{
    public function failed(Throwable $exception): void
    {
        $workflow = class_basename(static::class);
        $context = method_exists($this, 'failureContext') ? (string) $this->failureContext() : '';

        Log::error("Maria workflow failed: {$workflow}", [
            'error' => $exception->getMessage(),
            'context' => $context,
        ]);

        if ($exception instanceof ClaudeRateLimited) {
            return;
        }

        $email = Setting::get('staff_notification_email');
        if (! $email) {
            return;
        }

        try {
            Notification::route('mail', $email)->notify(
                new MariaWorkflowFailedNotification($workflow, $exception->getMessage(), $context)
            );
        } catch (Throwable $notifyError) {
            Log::error('Failed to send Maria workflow failure notification', ['error' => $notifyError->getMessage()]);
        }
    }
}
