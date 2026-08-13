<?php

namespace App\Jobs\Maria;

use App\Models\AssistantProfile;
use App\Services\Maria\DeadlineMonitorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MonitorDeadlines implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 3300;

    public function __construct(public int $profileId, public string $window) {}

    public function uniqueId(): string
    {
        return "{$this->profileId}:{$this->window}";
    }

    public function handle(DeadlineMonitorService $monitor): void
    {
        $monitor->run(AssistantProfile::findOrFail($this->profileId));
    }
}
