<?php

namespace App\Services;

use App\Models\KnowledgeChunk;
use App\Models\Setting;
use App\Services\Embeddings\EmbeddingProvider;
use Illuminate\Support\Collection;
use Pgvector\Laravel\Distance;

/**
 * Semantic fallback for whatever FaqMatcher misses. Mirrors FaqMatcher's shape:
 * same account/service scoping, same "cached active set, invalidated on write"
 * idea isn't needed here since pgvector does the filtering in the query itself.
 */
class KnowledgeRetriever
{
    // Cosine distance (0 = identical, 2 = opposite) below this is "relevant enough
    // to hand to Claude"; anything worse is dropped rather than injected as noise.
    // Tunable per-deployment the same way FaqMatcher::FUZZY_THRESHOLD is, since the
    // right cutoff depends on real traffic, not a value we can know up front.
    private const DEFAULT_MAX_DISTANCE = 0.4;

    public function __construct(private EmbeddingProvider $embeddings) {}

    /** @return Collection<int,KnowledgeChunk> */
    public function search(string $text, ?string $service, ?int $whatsappAccountId, int $limit = 4): Collection
    {
        $text = trim($text);
        if ($text === '') {
            return collect();
        }

        $vector = $this->embeddings->embed($text, 'query');

        $maxDistance = (float) (Setting::get('knowledge_max_distance') ?? self::DEFAULT_MAX_DISTANCE);

        return KnowledgeChunk::query()
            ->forAccount($whatsappAccountId)
            ->where(fn ($query) => $query->whereNull('service')->orWhere('service', $service))
            ->nearestNeighbors('embedding', $vector, Distance::Cosine)
            ->limit($limit)
            ->get()
            ->filter(fn (KnowledgeChunk $chunk) => $chunk->neighbor_distance <= $maxDistance)
            ->values();
    }
}
