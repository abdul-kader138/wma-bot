<?php

namespace App\Jobs\Maria;

use App\Models\AssistantProfile;
use App\Services\Maria\QualityReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class GenerateQualityReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $profileId, public string $weekStart) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping("quality-report:{$this->profileId}:{$this->weekStart}"))->expireAfter(600)];
    }

    public function handle(QualityReportService $service): void
    {
        $profile = AssistantProfile::find($this->profileId);
        if ($profile?->is_active && in_array('quality_report', $profile->enabled_workflows ?? [], true)) {
            $service->generate($profile, $this->weekStart);
        }
    }
}
