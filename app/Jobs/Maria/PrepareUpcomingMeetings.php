<?php

namespace App\Jobs\Maria;

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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public int $uniqueFor = 3300;

    public function __construct(public int $connectorId, public int $profileId, public string $window) {}

    public function uniqueId(): string
    {
        return "{$this->connectorId}:{$this->window}";
    }

    public function handle(MeetingPreparationService $meetings): void
    {
        $meetings->syncAndPrepare(ConnectorAccount::findOrFail($this->connectorId), AssistantProfile::findOrFail($this->profileId)->timezone);
    }
}
