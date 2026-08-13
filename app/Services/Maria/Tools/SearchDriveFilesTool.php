<?php

namespace App\Services\Maria\Tools;

use App\Models\User;
use App\Services\Maria\Google\GoogleDriveReadClient;
use App\Services\Maria\Tools\Concerns\UsesGoogleConnector;

class SearchDriveFilesTool extends BaseAssistantTool
{
    use UsesGoogleConnector;

    public function __construct(private readonly GoogleDriveReadClient $drive) {}

    public function name(): string
    {
        return 'search_drive_files';
    }

    public function description(): string
    {
        return 'Search read-only Google Drive file metadata by name.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => [
            'name' => ['type' => 'string'], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50],
        ]];
    }

    public function execute(User $owner, array $input): array
    {
        $result = $this->drive->search($this->googleConnector($owner), $input['name'] ?? null, (int) ($input['limit'] ?? 20));

        return $result['files'] ?? [];
    }
}
