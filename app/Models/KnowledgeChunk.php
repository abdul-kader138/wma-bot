<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

class KnowledgeChunk extends Model
{
    use HasNeighbors;

    protected $connection = 'pgvector';

    protected $fillable = [
        'knowledge_document_id', 'whatsapp_account_id', 'service',
        'language', 'chunk_index', 'content', 'content_hash', 'embedding',
    ];

    protected $casts = [
        'embedding' => Vector::class,
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'knowledge_document_id');
    }

    public function scopeForAccount($query, ?int $accountId)
    {
        return $query->where(function ($query) use ($accountId) {
            $query->whereNull('whatsapp_account_id')
                ->orWhere('whatsapp_account_id', $accountId);
        });
    }
}
