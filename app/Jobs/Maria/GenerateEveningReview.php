<?php

namespace App\Jobs\Maria;

use App\Jobs\Maria\Concerns\AlertsStaffOnFailure;
use App\Models\AssistantProfile;
use App\Services\Maria\EveningReviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateEveningReview implements ShouldBeUnique, ShouldQueue
{
    use AlertsStaffOnFailure, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** Space out retries so a transient shared-budget rate limit clears before the next attempt. */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public int $timeout = 120;

    public int $uniqueFor = 3600;

    public function __construct(public int $profileId, public string $date) {}

    public function uniqueId(): string
    {
        return "{$this->profileId}:{$this->date}";
    }

    public function handle(EveningReviewService $reviews): void
    {
        $profile = AssistantProfile::findOrFail($this->profileId);

        if (! $profile->is_active || ! $profile->runsWorkflow('evening_review')) {
            return;
        }

        $reviews->generate($profile);
    }
}
