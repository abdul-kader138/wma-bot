<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MariaTask extends Model
{
    protected $table = 'maria_tasks';

    protected $fillable = [
        'user_id', 'project_id', 'waiting_on_contact_id', 'task', 'owner_name',
        'source', 'source_reference', 'mission_score', 'relationship_score',
        'urgency_score', 'strategic_score', 'effort_score', 'risk_score',
        'priority_score', 'priority_reason', 'due_at', 'status', 'follow_up_at',
        'deduplication_key', 'possible_duplicate_of_id', 'approval_required',
        'approval_id', 'evidence_url', 'recurrence',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'follow_up_at' => 'datetime',
            'approval_required' => 'boolean',
            'recurrence' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(MariaProject::class, 'project_id');
    }

    public function waitingOn(): BelongsTo
    {
        return $this->belongsTo(MariaContact::class, 'waiting_on_contact_id');
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class);
    }

    public function possibleDuplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'possible_duplicate_of_id');
    }
}
