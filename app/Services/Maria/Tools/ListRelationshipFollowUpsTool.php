<?php

namespace App\Services\Maria\Tools;

use App\Models\RelationshipRecommendation;
use App\Models\User;

class ListRelationshipFollowUpsTool extends BaseAssistantTool
{
    public function name(): string
    {
        return 'list_relationship_followups';
    }

    public function description(): string
    {
        return 'List the owner’s open Daily Five relationship recommendations (people to engage) with the suggested warm path and draft note. Read-only and advisory: Maria never contacts anyone or acts on LinkedIn.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => [
            'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10],
        ]];
    }

    public function availableFor(User $owner): bool
    {
        $profile = $owner->assistantProfile;
        $workflows = $profile !== null ? ($profile->enabled_workflows ?? []) : [];

        return in_array('daily_five', $workflows, true);
    }

    public function execute(User $owner, array $input): array
    {
        return RelationshipRecommendation::query()
            ->where('user_id', $owner->id)
            ->where('status', 'pending')
            ->with('contact:id,full_name,role,organization,tier,stage')
            ->orderByDesc('score')
            ->limit(min(10, max(1, (int) ($input['limit'] ?? 5))))
            ->get()
            ->map(fn (RelationshipRecommendation $rec): array => [
                'id' => $rec->id,
                'contact' => $rec->contact?->only(['full_name', 'role', 'organization', 'tier', 'stage']),
                'score' => $rec->score,
                'relevance' => $rec->relevance,
                'warm_path' => $rec->warm_path,
                'suggested_comment' => $rec->suggested_comment,
                'connection_note' => $rec->connection_note,
                'recommended_stage' => $rec->recommended_stage,
                'next_action_at' => $rec->next_action_at?->toIso8601String(),
            ])
            ->all();
    }
}
