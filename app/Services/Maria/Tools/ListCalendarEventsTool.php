<?php

namespace App\Services\Maria\Tools;

use App\Models\User;
use App\Services\Maria\Google\GoogleCalendarReadClient;
use App\Services\Maria\Tools\Concerns\UsesGoogleConnector;
use Carbon\CarbonImmutable;

class ListCalendarEventsTool extends BaseAssistantTool
{
    use UsesGoogleConnector;

    public function __construct(private readonly GoogleCalendarReadClient $calendar) {}

    public function name(): string
    {
        return 'list_calendar_events';
    }

    public function description(): string
    {
        return 'Read Google Calendar events in a date range. Never creates or changes events.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => [
            'from' => ['type' => 'string', 'description' => 'ISO 8601 date/time'],
            'to' => ['type' => 'string', 'description' => 'ISO 8601 date/time'],
        ], 'required' => ['from', 'to']];
    }

    public function availableFor(User $owner): bool
    {
        return in_array('meeting_preparation', $owner->assistantProfile?->enabled_workflows ?? [], true)
            && $this->hasGoogleConnector($owner);
    }

    public function execute(User $owner, array $input): array
    {
        $result = $this->calendar->events(
            $this->googleConnector($owner), CarbonImmutable::parse($input['from']), CarbonImmutable::parse($input['to'])
        );

        return collect($result['items'] ?? [])->map(fn ($event) => [
            'id' => $event['id'] ?? null, 'summary' => $event['summary'] ?? '(Busy)',
            'start' => data_get($event, 'start.dateTime', data_get($event, 'start.date')),
            'end' => data_get($event, 'end.dateTime', data_get($event, 'end.date')),
            'attendees' => collect($event['attendees'] ?? [])->pluck('email')->all(),
            'html_link' => $event['htmlLink'] ?? null,
        ])->all();
    }
}
