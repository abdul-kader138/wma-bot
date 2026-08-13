<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcmProductionPlan extends Model
{
    protected $fillable = ['user_id', 'workflow_run_id', 'week_start', 'theme', 'source_notes', 'core_claims', 'owner_name', 'approval_deadline', 'production_package', 'claim_verification', 'status', 'generated_at', 'review_notes'];

    protected function casts(): array
    {
        return ['week_start' => 'date', 'core_claims' => 'array', 'approval_deadline' => 'datetime', 'production_package' => 'array', 'claim_verification' => 'array', 'generated_at' => 'datetime'];
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
