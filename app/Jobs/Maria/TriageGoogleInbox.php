<?php

namespace App\Jobs\Maria;

use App\Jobs\Maria\Concerns\AlertsStaffOnFailure;
use App\Models\ConnectorAccount;
use App\Services\Maria\EmailTriageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TriageGoogleInbox implements ShouldBeUnique, ShouldQueue
{
    use AlertsStaffOnFailure, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** Space out retries so a transient shared-budget rate limit clears before the next attempt. */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public int $timeout = 180;

    public int $uniqueFor = 1500;

    public function __construct(public int $connectorId, public string $window) {}

    public function uniqueId(): string
    {
        return "{$this->connectorId}:{$this->window}";
    }

    public function handle(EmailTriageService $triage): void
    {
        $connector = ConnectorAccount::with('user.assistantProfile')->findOrFail($this->connectorId);
        $profile = $connector->user?->assistantProfile;

        if ($profile === null || ! $profile->is_active || ! $profile->runsWorkflow('email_triage')) {
            return;
        }

        $triage->triage($connector);
    }
}
