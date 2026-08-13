<?php

namespace App\Jobs\Maria;

use App\Models\AcmProductionPlan;
use App\Services\Maria\AcmProductionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class GenerateAcmProductionPlan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $planId) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping("acm-production:{$this->planId}"))->expireAfter(900)];
    }

    public function handle(AcmProductionService $service): void
    {
        $plan = AcmProductionPlan::find($this->planId);
        if ($plan && in_array($plan->status, ['planned', 'blocked_claims'], true)) {
            $service->generate($plan);
        }
    }
}
