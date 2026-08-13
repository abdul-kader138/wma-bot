<?php

namespace App\Services\Maria\Tools;

use App\Models\MariaTask;
use App\Models\User;

class ListTasksTool extends BaseAssistantTool
{
    public function name(): string
    {
        return 'list_tasks';
    }

    public function description(): string
    {
        return 'List the owner’s open Maria tasks, optionally limited by status.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => [
            'status' => ['type' => 'string', 'enum' => ['open', 'scheduled', 'waiting', 'blocked', 'completed']],
            'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50],
        ]];
    }

    public function execute(User $owner, array $input): array
    {
        return MariaTask::query()->where('user_id', $owner->id)
            ->when($input['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByRaw('due_at is null, due_at asc')
            ->limit(min(50, max(1, (int) ($input['limit'] ?? 10))))
            ->get(['id', 'task', 'owner_name', 'status', 'priority_score', 'due_at', 'follow_up_at'])
            ->toArray();
    }
}
