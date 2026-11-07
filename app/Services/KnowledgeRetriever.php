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
        [$chunks, $maxDistance] = $this->rawCandidates($text, $service, $whatsappAccountId, $limit);

        return $chunks
            ->filter(fn (KnowledgeChunk $chunk) => $chunk->neighbor_distance <= $maxDistance)
            ->values();
    }

    /**
     * Same search as search(), but returns every candidate including ones that scored
     * worse than the configured distance threshold, annotated with whether each passes.
     * Used by the admin "test this knowledge" sandbox so staff can see "it WAS there,
     * just scored 0.52 against a 0.40 cutoff" instead of a bare empty result.
     *
     * @return array{max_distance: float, candidates: array}
     */
    public function explain(string $text, ?string $service, ?int $whatsappAccountId, int $limit = 10): array
    {
        [$chunks, $maxDistance] = $this->rawCandidates($text, $service, $whatsappAccountId, $limit);

        return [
            'max_distance' => $maxDistance,
            'candidates' => $chunks->map(fn (KnowledgeChunk $chunk) => [
                'chunk' => $chunk,
                'distance' => $chunk->neighbor_distance,
                'passes' => $chunk->neighbor_distance <= $maxDistance,
            ])->all(),
        ];
    }

    /** @return array{0: Collection<int,KnowledgeChunk>, 1: float} */
    private function rawCandidates(string $text, ?string $service, ?int $whatsappAccountId, int $limit): array
    {
        $text = trim($text);
        $maxDistance = (float) (Setting::get('knowledge_max_distance') ?? self::DEFAULT_MAX_DISTANCE);

        if ($text === '') {
            return [collect(), $maxDistance];
        }

        $vector = $this->embeddings->embed($text, 'query');

        $chunks = KnowledgeChunk::query()
            ->forAccount($whatsappAccountId)
            ->where(fn ($query) => $query->whereNull('service')->orWhere('service', $service))
            ->nearestNeighbors('embedding', $vector, Distance::Cosine)
            ->limit($limit)
            ->get();

        return [$chunks, $maxDistance];
    }
}
