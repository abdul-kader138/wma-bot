<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowRun extends Model
{
    protected $fillable = [
        'run_id', 'user_id', 'workflow_type', 'status', 'input_references',
        'source_gaps', 'structured_output', 'prompt_version', 'input_tokens',
        'output_tokens', 'estimated_cost', 'estimated_manual_minutes',
        'human_minutes', 'error', 'started_at', 'finished_at',
        'verified_time_saved_minutes', 'time_saving_verified_at', 'time_saving_verified_by',
    ];

    protected function casts(): array
    {
        return [
            'input_references' => 'array',
            'source_gaps' => 'array',
            'structured_output' => 'array',
            'estimated_cost' => 'decimal:6',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'time_saving_verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AssistantAction::class);
    }
}
