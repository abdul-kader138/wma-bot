<?php

namespace App\Services\Maria\Tools;

use App\Models\Approval;
use App\Models\User;

class ListApprovalsTool extends BaseAssistantTool
{
    public function name(): string
    {
        return 'list_pending_approvals';
    }

    public function description(): string
    {
        return 'List the owner’s approval requests that are still awaiting a decision. Read-only: this tool never approves, rejects, or executes anything.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => [
            'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20],
        ]];
    }

    public function execute(User $owner, array $input): array
    {
        return Approval::query()
            ->where('user_id', $owner->id)
            ->where('decision', 'pending')
            ->where('expires_at', '>', now())
            ->orderBy('expires_at')
            ->limit(min(20, max(1, (int) ($input['limit'] ?? 10))))
            ->get(['id', 'action_type', 'proposed_action', 'preview', 'recipient_channel', 'risk_level', 'expires_at'])
            ->toArray();
    }
}
