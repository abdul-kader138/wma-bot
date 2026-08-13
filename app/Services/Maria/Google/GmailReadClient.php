<?php

namespace App\Services\Maria\Google;

use App\Models\ConnectorAccount;

class GmailReadClient
{
    public function __construct(private readonly GoogleWorkspaceClient $google) {}

    public function listMessages(ConnectorAccount $connector, string $query = 'is:unread -category:promotions -category:social', int $limit = 20): array
    {
        return $this->google->get($connector, 'https://gmail.googleapis.com/gmail/v1/users/me/messages', [
            'q' => $query, 'maxResults' => min(100, max(1, $limit)),
        ]);
    }

    public function getMessage(ConnectorAccount $connector, string $messageId, string $format = 'metadata'): array
    {
        return $this->google->get($connector, "https://gmail.googleapis.com/gmail/v1/users/me/messages/{$messageId}", [
            'format' => $format,
            'metadataHeaders' => ['From', 'To', 'Cc', 'Subject', 'Date'],
        ]);
    }
}
