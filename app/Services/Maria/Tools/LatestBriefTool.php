<?php

namespace App\Services\Maria\Tools;

use App\Models\AssistantBrief;
use App\Models\User;

class LatestBriefTool extends BaseAssistantTool
{
    public function name(): string
    {
        return 'get_latest_brief';
    }

    public function description(): string
    {
        return 'Return the most recent structured brief Maria already generated (morning command brief, evening review, book portfolio review, Agverse review, or weekly quality report). Read-only; it does not generate a new brief.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => [
            'type' => ['type' => 'string', 'enum' => ['morning', 'evening', 'book_portfolio', 'agverse_opportunities', 'quality_report']],
        ], 'required' => ['type']];
    }

    public function execute(User $owner, array $input): array
    {
        $type = $input['type'] ?? 'morning';

        $brief = AssistantBrief::query()
            ->where('user_id', $owner->id)
            ->where('type', $type)
            ->latest('brief_date')
            ->first(['id', 'type', 'brief_date', 'content']);

        if (! $brief) {
            return ['error' => "No {$type} brief has been generated yet."];
        }

        return [
            'type' => $brief->type,
            'brief_date' => $brief->brief_date?->toDateString(),
            'content' => $brief->content,
        ];
    }
}
