<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MariaProject extends Model
{
    protected $table = 'maria_projects';

    protected $fillable = [
        'user_id', 'domain', 'name', 'desired_outcome', 'stage', 'priority',
        'owner_name', 'next_action', 'next_action_at', 'deadline_at', 'status',
        'blocker', 'confidentiality', 'last_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'next_action_at' => 'datetime',
            'deadline_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(MariaTask::class, 'project_id');
    }
}
