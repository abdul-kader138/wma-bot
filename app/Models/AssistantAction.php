<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantAction extends Model
{
    protected $fillable = [
        'user_id', 'workflow_run_id', 'approval_id', 'tool_name',
        'validated_input', 'content_hash', 'idempotency_key', 'status',
        'attempts', 'provider_confirmation_id', 'sanitized_result', 'error',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'validated_input' => 'array',
            'sanitized_result' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workflowRun(): BelongsTo
    {
        return $this->belongsTo(WorkflowRun::class);
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class);
    }
}
