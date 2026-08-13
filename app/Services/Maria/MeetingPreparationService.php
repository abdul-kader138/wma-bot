<?php

namespace App\Services\Maria;

use App\Models\ConnectorAccount;
use App\Models\Meeting;
use App\Services\Maria\Google\GoogleCalendarReadClient;
use Carbon\CarbonImmutable;

class MeetingPreparationService
{
    public function __construct(
        private readonly GoogleCalendarReadClient $calendar,
        private readonly StructuredWorkflowAgent $agent,
        private readonly PromptResolver $prompts,
    ) {}

    public function syncAndPrepare(ConnectorAccount $connector, string $timezone): array
    {
        $events = $this->calendar->events($connector, now($timezone), now($timezone)->addHours(48), 50)['items'] ?? [];
        $meetings = collect($events)->filter(fn ($event) => isset($event['id'], $event['start'], $event['end']))
            ->map(function ($event) use ($connector, $timezone) {
                $start = data_get($event, 'start.dateTime', data_get($event, 'start.date'));
                $end = data_get($event, 'end.dateTime', data_get($event, 'end.date'));

                return Meeting::updateOrCreate(
                    ['user_id' => $connector->user_id, 'calendar_event_id' => $event['id']],
                    [
                        'connector_account_id' => $connector->id, 'title' => $event['summary'] ?? '(Busy)',
                        'starts_at' => CarbonImmutable::parse($start, $timezone), 'ends_at' => CarbonImmutable::parse($end, $timezone),
                        'attendees' => $event['attendees'] ?? [],
                    ],
                );
            })->values();

        $candidates = $meetings->filter(fn (Meeting $meeting) => $meeting->preparation_status === 'pending' && ($meeting->tier === null || in_array($meeting->tier, ['A', 'B'])));
        if ($candidates->isEmpty()) {
            return $meetings->all();
        }

        $prompt = $this->prompts->active();
        $result = $this->agent->run(
            $prompt['content'],
            'Prepare concise meeting briefs using only these calendar records. Flag unknown identity/context as unverified: '.json_encode($candidates->toArray(), JSON_THROW_ON_ERROR),
            'save_meeting_briefs', 'Return preparation briefs for the supplied meetings.', $this->schema(),
        );
        foreach ($result['data']['meetings'] ?? [] as $brief) {
            Meeting::where('user_id', $connector->user_id)->where('calendar_event_id', $brief['calendar_event_id'])->update([
                'brief' => $brief, 'preparation_status' => 'prepared',
            ]);
        }

        return $meetings->map->refresh()->all();
    }

    private function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'meetings' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                'calendar_event_id' => ['type' => 'string'], 'verified_identity' => ['type' => 'string'],
                'objective' => ['type' => 'string'], 'shared_context' => ['type' => 'string'],
                'questions' => ['type' => 'array', 'maxItems' => 3, 'items' => ['type' => 'string']],
                'likely_interests_or_objections' => ['type' => 'array', 'items' => ['type' => 'string']],
                'sensitive_issues' => ['type' => 'array', 'items' => ['type' => 'string']],
                'unsupported_assumptions' => ['type' => 'array', 'items' => ['type' => 'string']],
                'desired_close' => ['type' => 'string'],
            ], 'required' => ['calendar_event_id', 'objective', 'questions', 'unsupported_assumptions', 'desired_close']]],
        ], 'required' => ['meetings']];
    }
}
