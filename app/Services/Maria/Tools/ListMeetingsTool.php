<?php

namespace App\Services\Maria\Tools;

use App\Models\Meeting;
use App\Models\User;

class ListMeetingsTool extends BaseAssistantTool
{
    public function name(): string
    {
        return 'list_meetings';
    }

    public function description(): string
    {
        return 'List the owner’s upcoming meetings that Maria has already synced from the calendar, with their preparation status and objective. Read-only. Use list_calendar_events instead only when you need the live calendar rather than the synced record.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => [
            'days_ahead' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 30],
            'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20],
        ]];
    }

    public function execute(User $owner, array $input): array
    {
        $days = min(30, max(1, (int) ($input['days_ahead'] ?? 7)));

        return Meeting::query()
            ->where('user_id', $owner->id)
            ->where('starts_at', '>=', now()->subHours(1))
            ->where('starts_at', '<=', now()->addDays($days))
            ->orderBy('starts_at')
            ->limit(min(20, max(1, (int) ($input['limit'] ?? 10))))
            ->get(['id', 'title', 'starts_at', 'ends_at', 'tier', 'objective', 'preparation_status', 'follow_up_at'])
            ->map(fn (Meeting $meeting): array => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'starts_at' => $meeting->starts_at?->toIso8601String(),
                'ends_at' => $meeting->ends_at?->toIso8601String(),
                'tier' => $meeting->tier,
                'objective' => $meeting->objective,
                'preparation_status' => $meeting->preparation_status,
            ])
            ->all();
    }
}
