<?php

namespace App\Services\Maria\Tools;

use App\Models\MariaProject;
use App\Models\User;

class ListProjectsTool extends BaseAssistantTool
{
    public function name(): string
    {
        return 'list_projects';
    }

    public function description(): string
    {
        return 'List the owner’s Maria projects with their stage, owner, next action, and deadline. Read-only.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => [
            'domain' => ['type' => 'string', 'description' => 'Optional domain code such as PER, ACM, AGV, BKS, REL, COM, MTG, LEG, XPF.'],
            'stage' => ['type' => 'string', 'enum' => ['idea', 'defined', 'active', 'waiting', 'review', 'complete', 'paused', 'archived']],
            'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50],
        ]];
    }

    public function execute(User $owner, array $input): array
    {
        return MariaProject::query()->where('user_id', $owner->id)
            ->when($input['domain'] ?? null, fn ($query, $domain) => $query->where('domain', strtoupper((string) $domain)))
            ->when(
                $input['stage'] ?? null,
                fn ($query, $stage) => $query->where('stage', $stage),
                fn ($query) => $query->whereNotIn('stage', ['complete', 'archived']),
            )
            ->orderByRaw('deadline_at is null, deadline_at asc')
            ->limit(min(50, max(1, (int) ($input['limit'] ?? 15))))
            ->get(['id', 'domain', 'name', 'desired_outcome', 'stage', 'priority', 'owner_name', 'next_action', 'next_action_at', 'deadline_at', 'status', 'blocker'])
            ->toArray();
    }
}
