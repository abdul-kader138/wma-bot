<?php

namespace App\Services\Maria\Google;

use App\Models\ConnectorAccount;
use DateTimeInterface;

class GoogleCalendarReadClient
{
    public function __construct(private readonly GoogleWorkspaceClient $google) {}

    public function events(ConnectorAccount $connector, DateTimeInterface $from, DateTimeInterface $to, int $limit = 50): array
    {
        return $this->google->get($connector, 'https://www.googleapis.com/calendar/v3/calendars/primary/events', [
            'timeMin' => $from->format(DATE_RFC3339), 'timeMax' => $to->format(DATE_RFC3339),
            'singleEvents' => 'true', 'orderBy' => 'startTime', 'maxResults' => min(250, max(1, $limit)),
        ]);
    }
}
