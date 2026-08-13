<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionReconciliation extends Model
{
    protected $fillable = ['user_id', 'assistant_action_id', 'provider', 'status', 'reason', 'provider_evidence', 'resolution_notes', 'resolved_by', 'resolved_at'];

    protected function casts(): array
    {
        return ['provider_evidence' => 'array', 'resolved_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(AssistantAction::class, 'assistant_action_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
