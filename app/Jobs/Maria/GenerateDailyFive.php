<?php

namespace App\Jobs\Maria;

use App\Models\AssistantProfile;
use App\Services\Maria\DailyFiveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class GenerateDailyFive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $profileId, public string $date) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping("daily-five:{$this->profileId}:{$this->date}"))->expireAfter(600)];
    }

    public function handle(DailyFiveService $service): void
    {
        $profile = AssistantProfile::find($this->profileId);
        if ($profile?->is_active && in_array('daily_five', $profile->enabled_workflows ?? [], true)) {
            $service->generate($profile, $this->date);
        }
    }
}
