<?php

namespace App\Jobs;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Services\Embeddings\EmbeddingProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Re-chunks and re-embeds a KnowledgeDocument from scratch. Runs on every save
 * (see KnowledgeDocument::booted()) via Horizon's redis queue — embedding is an
 * external HTTP call per batch and must never block the admin request.
 */
class IngestKnowledgeDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Approximate words per chunk / overlap — no tokenizer available, and exact
    // token counts don't matter here: the embedding model truncates safely, and
    // these windows are comfortably under any provider's input limit.
    private const WORDS_PER_CHUNK = 400;

    private const WORD_OVERLAP = 50;

    public int $tries = 3;

    public function __construct(public KnowledgeDocument $document) {}

    public function handle(EmbeddingProvider $embeddings): void
    {
        $this->document->chunks()->delete();

        if (! $this->document->is_active || trim($this->document->content) === '') {
            return;
        }

        $chunks = $this->chunk($this->document->content);
        if (empty($chunks)) {
            return;
        }

        try {
            $vectors = $embeddings->embedMany($chunks, 'document');
        } catch (\Throwable $e) {
            Log::error('Knowledge document ingestion failed to embed chunks', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        // A document with no account scoping at all is treated as global (null),
        // same semantics as Faq's null service meaning "applies to everything".
        $accountIds = $this->document->account_ids ?: [null];

        foreach ($accountIds as $accountId) {
            foreach ($chunks as $index => $content) {
                KnowledgeChunk::create([
                    'knowledge_document_id' => $this->document->id,
                    'whatsapp_account_id' => $accountId,
                    'service' => $this->document->service,
                    'language' => $this->document->language,
                    'chunk_index' => $index,
                    'content' => $content,
                    'embedding' => $vectors[$index],
                ]);
            }
        }
    }

    /** @return array<int,string> */
    private function chunk(string $content): array
    {
        $words = preg_split('/\s+/u', trim($content));
        $words = array_values(array_filter($words, fn ($w) => $w !== ''));

        if (empty($words)) {
            return [];
        }

        $chunks = [];
        $step = self::WORDS_PER_CHUNK - self::WORD_OVERLAP;

        for ($start = 0; $start < count($words); $start += $step) {
            $slice = array_slice($words, $start, self::WORDS_PER_CHUNK);
            $chunks[] = implode(' ', $slice);

            if ($start + self::WORDS_PER_CHUNK >= count($words)) {
                break;
            }
        }

        return $chunks;
    }
}
