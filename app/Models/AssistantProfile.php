<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssistantProfile extends Model
{
    protected $fillable = [
        'user_id', 'timezone', 'language', 'working_hours_start', 'working_hours_end',
        'morning_brief_at', 'evening_review_at', 'enabled_workflows',
        'weekly_production_day', 'weekly_production_at',
        'notification_preferences', 'voice_preferences', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'enabled_workflows' => 'array',
            'notification_preferences' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channelIdentities(): HasMany
    {
        return $this->hasMany(AssistantChannelIdentity::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(AssistantConversation::class);
    }
}
