<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgverseOpportunity extends Model
{
    protected $fillable = [
        'user_id', 'name', 'organization', 'summary', 'expected_value', 'currency',
        'value_score', 'strategic_fit_score', 'urgency_score', 'evidence_score',
        'effort_score', 'risk_score', 'priority_score', 'verified_facts', 'hypotheses',
        'evidence_links', 'next_step', 'next_step_owner', 'next_step_at',
        'approval_required', 'stage', 'status',
    ];

    protected function casts(): array
    {
        return [
            'expected_value' => 'decimal:2', 'verified_facts' => 'array', 'hypotheses' => 'array',
            'evidence_links' => 'array', 'next_step_at' => 'datetime', 'approval_required' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
