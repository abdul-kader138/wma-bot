<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantAlert extends Model
{
    protected $fillable = [
        'user_id', 'type', 'severity', 'subject_type', 'subject_id', 'state_hash',
        'status', 'message', 'first_seen_at', 'last_seen_at', 'acknowledged_at',
    ];

    protected function casts(): array
    {
        return ['subject_id' => 'integer', 'first_seen_at' => 'datetime', 'last_seen_at' => 'datetime', 'acknowledged_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
