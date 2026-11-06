<?php

namespace App\Services\Embeddings;

use Illuminate\Support\Facades\Http;

/**
 * Deliberately separate from ClaudeClient: embeddings only feed KnowledgeRetriever's
 * vector search, never the chat model, so swapping the answering LLM never touches this.
 */
class VoyageEmbeddingProvider implements EmbeddingProvider
{
    private const URL = 'https://api.voyageai.com/v1/embeddings';

    public function embed(string $text, string $inputType = 'document'): array
    {
        return $this->embedMany([$text], $inputType)[0];
    }

    public function embedMany(array $texts, string $inputType = 'document'): array
    {
        if (empty($texts)) {
            return [];
        }

        $response = Http::withToken(config('services.embeddings.voyage.key'))
            // A full batch (up to 500 texts — see IngestKnowledgeDocument::EMBED_BATCH_SIZE)
            // can take noticeably longer than a single-text query embed.
            ->timeout(90)
            ->retry(2, 500, fn (\Throwable $e) => $e instanceof \Illuminate\Http\Client\ConnectionException
                || ($e instanceof \Illuminate\Http\Client\RequestException && in_array($e->response->status(), [429, 500, 502, 503], true)))
            ->post(self::URL, [
                'input' => $texts,
                'model' => config('services.embeddings.voyage.model'),
                'input_type' => $inputType,
                'output_dimension' => (int) config('services.embeddings.voyage.dimensions', 512),
            ])
            ->throw()
            ->json();

        return collect($response['data'] ?? [])
            ->sortBy('index')
            ->pluck('embedding')
            ->all();
    }
}
