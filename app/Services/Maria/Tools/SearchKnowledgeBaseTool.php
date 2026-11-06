<?php

namespace App\Services\Maria\Tools;

use App\Models\User;
use App\Services\KnowledgeRetriever;

class SearchKnowledgeBaseTool extends BaseAssistantTool
{
    public function __construct(private readonly KnowledgeRetriever $knowledge) {}

    public function name(): string
    {
        return 'search_knowledge_base';
    }

    public function description(): string
    {
        return 'Search the curated business knowledge base (air tickets, driving licence, immigration, CAF, etc.) for information to answer a question. Returns the most relevant reference snippets, or none if nothing matches closely enough.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => [
            'query' => ['type' => 'string', 'description' => 'The question or topic to search for.'],
            'service' => ['type' => 'string', 'description' => 'Narrow the search to one business line\'s slug, if known.'],
        ], 'required' => ['query']];
    }

    public function availableFor(User $owner): bool
    {
        return true;
    }

    public function execute(User $owner, array $input): array
    {
        // Maria isn't tied to a single WhatsApp account/customer, so this always
        // searches the account-agnostic (global) slice of the knowledge base.
        $chunks = $this->knowledge->search(
            $input['query'] ?? '',
            $input['service'] ?? null,
            null,
        );

        if ($chunks->isEmpty()) {
            return ['results' => [], 'note' => 'No sufficiently relevant knowledge base entries found.'];
        }

        return ['results' => $chunks->map(fn ($chunk) => [
            'service' => $chunk->service,
            'content' => $chunk->content,
        ])->all()];
    }
}
