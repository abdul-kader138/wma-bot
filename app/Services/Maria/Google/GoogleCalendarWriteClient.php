<?php

namespace App\Services\Maria\Google;

use App\Models\ConnectorAccount;
use Illuminate\Validation\ValidationException;

class GoogleCalendarWriteClient
{
    public const SCOPE = 'https://www.googleapis.com/auth/calendar.events';

    public function __construct(private readonly GoogleWorkspaceClient $google) {}

    public function createEvent(ConnectorAccount $connector, array $event, string $providerEventId): array
    {
        if (! in_array(self::SCOPE, $connector->scopes ?? [], true)) {
            throw ValidationException::withMessages(['connector' => 'Reconnect Google using “Enable approved writes” to grant Calendar write permission.']);
        }

        return $this->google->post($connector, 'https://www.googleapis.com/calendar/v3/calendars/primary/events', [
            'id' => $providerEventId, 'summary' => $event['title'], 'description' => $event['description'] ?? null,
            'location' => $event['location'] ?? null,
            'start' => ['dateTime' => $event['starts_at'], 'timeZone' => $event['timezone']],
            'end' => ['dateTime' => $event['ends_at'], 'timeZone' => $event['timezone']],
            'attendees' => collect($event['attendees'] ?? [])->map(fn ($email) => ['email' => $email])->all(),
        ]);
    }
}
