<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelationshipRecommendation extends Model
{
    protected $fillable = [
        'user_id', 'maria_contact_id', 'workflow_run_id', 'recommendation_date', 'score',
        'relevance', 'warm_path', 'suggested_comment', 'connection_note', 'follow_up',
        'recommended_stage', 'next_action_at', 'status', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected function casts(): array
    {
        return ['recommendation_date' => 'date', 'score' => 'integer', 'next_action_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(MariaContact::class, 'maria_contact_id');
    }

    public function workflowRun(): BelongsTo
    {
        return $this->belongsTo(WorkflowRun::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
