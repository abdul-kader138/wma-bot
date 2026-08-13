<?php

namespace App\Services\Maria\Tools;

use App\Models\User;
use App\Services\Maria\Google\GmailReadClient;
use App\Services\Maria\Tools\Concerns\UsesGoogleConnector;

class ListPriorityEmailsTool extends BaseAssistantTool
{
    use UsesGoogleConnector;

    public function __construct(private readonly GmailReadClient $gmail) {}

    public function name(): string
    {
        return 'list_priority_emails';
    }

    public function description(): string
    {
        return 'Read recent matching Gmail messages and return metadata and snippets. Never sends or modifies email.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => [
            'query' => ['type' => 'string'], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20],
        ]];
    }

    public function execute(User $owner, array $input): array
    {
        $connector = $this->googleConnector($owner);
        $limit = min(20, max(1, (int) ($input['limit'] ?? 10)));
        $list = $this->gmail->listMessages($connector, $input['query'] ?? 'is:unread -category:promotions -category:social', $limit);

        return collect($list['messages'] ?? [])->take($limit)->map(function ($message) use ($connector) {
            $data = $this->gmail->getMessage($connector, $message['id']);
            $headers = collect(data_get($data, 'payload.headers', []))->pluck('value', 'name');

            return [
                'id' => $data['id'] ?? $message['id'], 'thread_id' => $data['threadId'] ?? null,
                'from' => $headers->get('From'), 'subject' => $headers->get('Subject'),
                'date' => $headers->get('Date'), 'snippet' => $data['snippet'] ?? null,
                'labels' => $data['labelIds'] ?? [],
            ];
        })->values()->all();
    }
}
