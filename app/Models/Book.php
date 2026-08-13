<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Book extends Model
{
    protected $fillable = [
        'user_id', 'exact_title', 'subtitle', 'credits', 'edition', 'stage', 'manuscript_url',
        'current_milestone', 'milestone_owner', 'milestone_due_at', 'blocker', 'contributors',
        'publication_target', 'marketing_status', 'next_action', 'next_action_at', 'status',
    ];

    protected function casts(): array
    {
        return ['contributors' => 'array', 'milestone_due_at' => 'datetime', 'publication_target' => 'date', 'next_action_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
