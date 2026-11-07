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
        'source_disk', 'source_path', 'is_active', 'reviewed_at',
    ];

    protected $casts = [
        'account_ids' => 'array',
        'is_active' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // A staff edit to any of these "counts" as a review — using saving() rather
        // than saved() avoids recursing back through this same booted() hook's own
        // saved() dispatch below.
        static::saving(function (KnowledgeDocument $doc) {
            if ($doc->isDirty(['content', 'title', 'service', 'language'])) {
                $doc->reviewed_at = now();
            }
        });

        static::saved(function (KnowledgeDocument $doc) {
            if ($doc->source_path && $doc->wasChanged('source_path')) {
                ExtractKnowledgeDocumentText::dispatch($doc);

                return;
            }

            // Re-chunking/re-embedding is an external HTTP round trip per batch — skip
            // it for saves that didn't touch anything that could change the chunks
            // themselves (e.g. a title-only edit), not just every save unconditionally.
            if ($doc->wasChanged(['content', 'service', 'language', 'account_ids', 'is_active'])) {
                IngestKnowledgeDocument::dispatch($doc);
            }
        });
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class);
    }

    /** Whether this entry has gone too long without being written or reviewed. */
    public function isStale(): bool
    {
        $months = (int) Setting::get('knowledge_stale_after_months', 0);
        if ($months <= 0) {
            return false;
        }

        $reviewedAt = $this->reviewed_at ?? $this->created_at;

        return $reviewedAt !== null && $reviewedAt->lt(now()->subMonths($months));
    }

    public function scopeStale($query)
    {
        $months = (int) Setting::get('knowledge_stale_after_months', 0);
        if ($months <= 0) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereRaw('COALESCE(reviewed_at, created_at) < ?', [now()->subMonths($months)]);
    }
}
