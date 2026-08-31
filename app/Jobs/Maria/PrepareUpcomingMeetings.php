<?php

namespace App\Jobs\Maria;

use App\Jobs\Maria\Concerns\AlertsStaffOnFailure;
use App\Models\AssistantProfile;
use App\Models\ConnectorAccount;
use App\Services\Maria\MeetingPreparationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PrepareUpcomingMeetings implements ShouldBeUnique, ShouldQueue
{
    use AlertsStaffOnFailure, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** Space out retries so a transient shared-budget rate limit clears before the next attempt. */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public int $timeout = 180;

    public int $uniqueFor = 3300;

    public function __construct(public int $connectorId, public int $profileId, public string $window) {}

    public function uniqueId(): string
    {
        return "{$this->connectorId}:{$this->window}";
    }

    public function handle(MeetingPreparationService $meetings): void
    {
        $profile = AssistantProfile::findOrFail($this->profileId);

        if (! $profile->is_active || ! $profile->runsWorkflow('meeting_preparation')) {
            return;
        }

        $meetings->syncAndPrepare(ConnectorAccount::findOrFail($this->connectorId), $profile->timezone);
    }
}
