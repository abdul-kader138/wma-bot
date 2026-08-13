<?php

namespace App\Services\Maria\Google;

use App\Models\ConnectorAccount;

class GoogleDriveReadClient
{
    public function __construct(private readonly GoogleWorkspaceClient $google) {}

    public function search(ConnectorAccount $connector, ?string $name = null, int $limit = 50): array
    {
        $query = 'trashed = false';
        if (filled($name)) {
            $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $name);
            $query .= " and name contains '{$escaped}'";
        }

        return $this->google->get($connector, 'https://www.googleapis.com/drive/v3/files', [
            'q' => $query, 'pageSize' => min(100, max(1, $limit)),
            'orderBy' => 'modifiedTime desc',
            'fields' => 'nextPageToken,files(id,name,mimeType,modifiedTime,webViewLink,owners(displayName,emailAddress))',
        ]);
    }
}
