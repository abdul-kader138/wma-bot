<?php

namespace App\Jobs\Maria;

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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 3600;

    public function __construct(public int $profileId, public string $date) {}

    public function uniqueId(): string
    {
        return "{$this->profileId}:{$this->date}";
    }

    public function handle(EveningReviewService $reviews): void
    {
        $reviews->generate(AssistantProfile::findOrFail($this->profileId));
    }
}
