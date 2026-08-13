<?php

namespace App\Services\Maria;

use App\Models\Approval;
use App\Models\AssistantAction;
use App\Models\User;
use App\Models\WorkflowRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssistantActionService
{
    public function __construct(private readonly ApprovalService $approvals) {}

    public function reserve(
        User $owner,
        string $toolName,
        array $input,
        ?WorkflowRun $workflowRun = null,
        ?Approval $approval = null,
        bool $approvalRequired = true,
    ): AssistantAction {
        $contentHash = $this->approvals->contentHash($input);

        if ($approvalRequired) {
            $this->assertValidApproval($approval, $owner, $contentHash);
        }

        $idempotencyKey = hash('sha256', implode('|', [
            $workflowRun?->run_id ?? ($approval ? "approval:{$approval->id}" : 'manual'),
            $owner->id,
            $toolName,
            $contentHash,
        ]));

        return AssistantAction::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'user_id' => $owner->id,
                'workflow_run_id' => $workflowRun?->id,
                'approval_id' => $approval?->id,
                'tool_name' => $toolName,
                'validated_input' => $input,
                'content_hash' => $contentHash,
                'status' => 'not_executed',
            ],
        );
    }

    public function markExecuting(AssistantAction $action): AssistantAction
    {
        return DB::transaction(function () use ($action) {
            $action = AssistantAction::query()->lockForUpdate()->findOrFail($action->id);

            if (! in_array($action->status, ['not_executed', 'failed'], true)) {
                throw ValidationException::withMessages(['action' => 'This action cannot be executed again.']);
            }

            $action->update(['status' => 'executing', 'attempts' => $action->attempts + 1, 'error' => null]);

            return $action->refresh();
        });
    }

    public function markCompleted(AssistantAction $action, string $confirmationId, array $result = []): AssistantAction
    {
        if ($action->status !== 'executing') {
            throw ValidationException::withMessages(['action' => 'Only an executing action can be completed.']);
        }

        $action->update([
            'status' => 'completed',
            'provider_confirmation_id' => $confirmationId,
            'sanitized_result' => $result ?: null,
            'executed_at' => now(),
            'error' => null,
        ]);

        return $action->refresh();
    }

    public function markFailed(AssistantAction $action, string $error): AssistantAction
    {
        if ($action->status !== 'executing') {
            throw ValidationException::withMessages(['action' => 'Only an executing action can fail.']);
        }

        $action->update(['status' => 'failed', 'error' => $error]);

        return $action->refresh();
    }

    private function assertValidApproval(?Approval $approval, User $owner, string $contentHash): void
    {
        if (! $approval
            || $approval->user_id !== $owner->id
            || $approval->decision !== 'approved'
            || $approval->expires_at->isPast()
            || ! hash_equals($approval->content_hash, $contentHash)) {
            throw ValidationException::withMessages([
                'approval' => 'An approved action-specific approval is required.',
            ]);
        }
    }
}
