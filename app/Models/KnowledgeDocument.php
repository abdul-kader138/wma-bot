<?php

namespace App\Models;

use App\Jobs\ExtractKnowledgeDocumentText;
use App\Jobs\IngestKnowledgeDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Staff-authored (or uploaded) source text for RAG. Lives on the dedicated
 * 'pgvector' connection (config/database.php) — a separate Postgres instance
 * from the app's main MySQL database — so it can never have a real foreign
 * key or Eloquent relationship to a table on the main connection (e.g.
 * whatsapp_accounts): a single SQL query can't join across two database
 * servers. account_ids is a plain JSON array of IDs, compared by value.
 *
 * Chunking + embedding happens asynchronously (IngestKnowledgeDocument, or
 * ExtractKnowledgeDocumentText first for an uploaded file) — never inline on
 * save, since both involve an external HTTP round trip.
 */
class KnowledgeDocument extends Model
{
    protected $connection = 'pgvector';

    protected $fillable = [
        'account_ids', 'service', 'title', 'language', 'content',
        'source_disk', 'source_path', 'is_active',
    ];

    protected $casts = [
        'account_ids' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (KnowledgeDocument $doc) {
            if ($doc->source_path && $doc->wasChanged('source_path')) {
                ExtractKnowledgeDocumentText::dispatch($doc);

                return;
            }

            IngestKnowledgeDocument::dispatch($doc);
        });
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class);
    }
}
