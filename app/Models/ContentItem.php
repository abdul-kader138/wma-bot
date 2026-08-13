<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentItem extends Model
{
    protected $fillable = [
        'user_id', 'workflow_run_id', 'brand', 'content_pillar', 'audience', 'source_idea',
        'source_url', 'core_claims', 'source_hash', 'master_draft', 'derivatives',
        'claim_verification', 'status', 'review_notes', 'generated_at',
    ];

    protected function casts(): array
    {
        return ['core_claims' => 'array', 'derivatives' => 'array', 'claim_verification' => 'array', 'generated_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workflowRun(): BelongsTo
    {
        return $this->belongsTo(WorkflowRun::class);
    }
}
