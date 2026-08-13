<?php

namespace App\Jobs\Maria;

use App\Models\AssistantProfile;
use App\Services\Maria\AgverseOpportunityReviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ReviewAgverseOpportunities implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $profileId, public string $reviewDate) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping("agverse-review:{$this->profileId}:{$this->reviewDate}"))->expireAfter(600)];
    }

    public function handle(AgverseOpportunityReviewService $service): void
    {
        $profile = AssistantProfile::find($this->profileId);
        if ($profile?->is_active && in_array('agverse_opportunity_review', $profile->enabled_workflows ?? [], true)) {
            $service->review($profile, $this->reviewDate);
        }
    }
}
