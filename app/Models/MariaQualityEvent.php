<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MariaQualityEvent extends Model
{
    protected $fillable = ['user_id', 'workflow_run_id', 'reported_by', 'event_type', 'category', 'severity', 'description', 'expected_result', 'actual_result', 'resolution', 'status', 'occurred_at', 'resolved_at'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workflowRun(): BelongsTo
    {
        return $this->belongsTo(WorkflowRun::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
