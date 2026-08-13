<?php

namespace App\Jobs\Maria;

use App\Models\AssistantProfile;
use App\Services\Maria\BookPortfolioReviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class GenerateBookPortfolioReview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $profileId, public string $weekDate) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping("book-portfolio:{$this->profileId}:{$this->weekDate}"))->expireAfter(900)];
    }

    public function handle(BookPortfolioReviewService $service): void
    {
        $profile = AssistantProfile::find($this->profileId);
        if ($profile?->is_active && in_array('book_portfolio_review', $profile->enabled_workflows ?? [], true)) {
            $service->generate($profile, $this->weekDate);
        }
    }
}
