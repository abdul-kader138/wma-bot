<?php

namespace App\Services\Maria;

use App\Models\Approval;
use App\Models\User;
use App\Models\WorkflowRun;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function request(
        User $owner,
        string $actionType,
        string $proposedAction,
        array $content,
        ?WorkflowRun $workflowRun = null,
        ?string $preview = null,
        ?string $recipientChannel = null,
        array $attachments = [],
        string $riskLevel = 'low',
        ?\DateTimeInterface $expiresAt = null,
    ): Approval {
        $this->validateRisk($riskLevel);

        return Approval::create([
            'user_id' => $owner->id,
            'workflow_run_id' => $workflowRun?->id,
            'action_type' => $actionType,
            'proposed_action' => $proposedAction,
            'proposed_content' => $content,
            'preview' => $preview,
            'recipient_channel' => $recipientChannel,
            'attachments' => $attachments ?: null,
            'risk_level' => $riskLevel,
            'decision' => 'pending',
            'expires_at' => $expiresAt ?? now()->addDay(),
            'content_hash' => $this->contentHash($content),
        ]);
    }

    public function approve(Approval $approval, User $actor, array $currentContent): Approval
    {
        if ($approval->decision === 'pending' && $approval->expires_at->isPast()) {
            $approval->update(['decision' => 'expired', 'decided_at' => now()]);

            throw ValidationException::withMessages(['approval' => 'This approval has expired.']);
        }

        return DB::transaction(function () use ($approval, $actor, $currentContent) {
            $approval = Approval::query()->lockForUpdate()->findOrFail($approval->id);
            $this->assertOwner($approval, $actor);
            $this->assertPending($approval);

            if (! hash_equals($approval->content_hash, $this->contentHash($currentContent))) {
                throw ValidationException::withMessages([
                    'approval' => 'The proposed action changed and requires a new approval.',
                ]);
            }

            $approval->update([
                'decision' => 'approved',
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ]);

            return $approval->refresh();
        });
    }

    public function decide(Approval $approval, User $actor, string $decision, ?string $notes = null): Approval
    {
        if (! in_array($decision, ['delayed', 'rejected'], true)) {
            throw ValidationException::withMessages(['decision' => 'Unsupported approval decision.']);
        }

        $this->assertOwner($approval, $actor);
        $this->assertPending($approval);

        $approval->update([
            'decision' => $decision,
            'decided_by' => $actor->id,
            'decided_at' => now(),
            'audit_notes' => $notes,
        ]);

        return $approval->refresh();
    }

    public function expireDue(): int
    {
        return Approval::query()
            ->where('decision', 'pending')
            ->where('expires_at', '<=', now())
            ->update(['decision' => 'expired', 'decided_at' => now()]);
    }

    public function contentHash(array $content): string
    {
        return hash('sha256', json_encode($this->canonicalize($content), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    private function canonicalize(array $value): array
    {
        if (Arr::isAssoc($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->canonicalize($item);
            }
        }

        return $value;
    }

    private function assertPending(Approval $approval): void
    {
        if ($approval->decision !== 'pending') {
            throw ValidationException::withMessages(['approval' => 'This approval is no longer pending.']);
        }

        if ($approval->expires_at->isPast()) {
            throw ValidationException::withMessages(['approval' => 'This approval has expired.']);
        }
    }

    private function assertOwner(Approval $approval, User $actor): void
    {
        if ($approval->user_id !== $actor->id && ! $actor->isAdmin()) {
            throw ValidationException::withMessages(['approval' => 'You cannot decide this approval.']);
        }
    }

    private function validateRisk(string $riskLevel): void
    {
        if (! in_array($riskLevel, ['low', 'medium', 'high', 'critical'], true)) {
            throw ValidationException::withMessages(['risk_level' => 'Invalid risk level.']);
        }
    }
}
