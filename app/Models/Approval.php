<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Approval extends Model
{
    protected $fillable = [
        'user_id', 'workflow_run_id', 'action_type', 'proposed_action', 'proposed_content', 'preview',
        'recipient_channel', 'attachments', 'risk_level', 'decision', 'decided_by',
        'decided_at', 'expires_at', 'content_hash', 'audit_notes',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'proposed_content' => 'array',
            'decided_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function workflowRun(): BelongsTo
    {
        return $this->belongsTo(WorkflowRun::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AssistantAction::class);
    }

    public function isPendingAndCurrent(): bool
    {
        return $this->decision === 'pending' && $this->expires_at->isFuture();
    }
}
