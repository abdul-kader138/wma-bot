<?php

namespace App\Jobs\Maria;

use App\Models\ActionReconciliation;
use App\Models\AssistantAction;
use App\Services\Maria\AssistantActionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * A worker that dies (deploy, OOM kill, timeout) between a Google API call succeeding
 * and markCompleted() running leaves an AssistantAction permanently in 'executing' with
 * no automatic path to reconciliation — see ApprovedGoogleActionService::execute(),
 * whose catch() block that normally creates the ActionReconciliation row never runs on
 * a hard process kill. This sweep finds and flags those actions instead of leaving them
 * stuck forever with no operator visibility.
 */
class ReconcileStuckActions implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 300;

    public function handle(AssistantActionService $actions): void
    {
        AssistantAction::query()
            ->where('status', 'executing')
            ->where('updated_at', '<', now()->subMinutes(10))
            ->whereDoesntHave('reconciliation')
            ->each(function (AssistantAction $action) use ($actions) {
                $actions->markFailed($action, 'Action timed out in the executing state (worker likely crashed or was killed mid-call) and was flagged for manual reconciliation.');

                ActionReconciliation::firstOrCreate(['assistant_action_id' => $action->id], [
                    'user_id' => $action->user_id, 'provider' => 'google', 'status' => 'pending',
                    'reason' => 'Action was stuck in executing for over 10 minutes with no completion or failure recorded. The provider call may have actually succeeded — verify before retrying to avoid a duplicate action.',
                ]);
            });
    }
}
