<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantBrief extends Model
{
    protected $fillable = ['user_id', 'workflow_run_id', 'type', 'brief_date', 'content'];

    protected function casts(): array
    {
        return ['brief_date' => 'date', 'content' => 'array'];
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
