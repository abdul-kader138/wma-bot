<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<int, string>|null $enabled_workflows
 * @property bool $is_active
 * @property bool $external_actions_enabled
 * @property string|null $timezone
 * @property string|null $language
 */
class AssistantProfile extends Model
{
    protected $fillable = [
        'user_id', 'timezone', 'language', 'working_hours_start', 'working_hours_end',
        'morning_brief_at', 'evening_review_at', 'enabled_workflows',
        'weekly_production_day', 'weekly_production_at',
        'notification_preferences', 'voice_preferences', 'is_active',
        'external_actions_enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled_workflows' => 'array',
            'notification_preferences' => 'array',
            'is_active' => 'boolean',
            'external_actions_enabled' => 'boolean',
        ];
    }

    /**
     * The five "core" workflows run for any active profile that has never had its
     * workflow list configured (enabled_workflows is null). Once the list exists —
     * even as [] — every workflow, core or not, must be named in it explicitly.
     * The remaining workflows (daily_five, book/agverse/acm/quality) are opt-in and
     * gate themselves the same way directly in the scheduler and their jobs.
     */
    private const CORE_WORKFLOWS = [
        'morning_brief', 'evening_review', 'email_triage', 'meeting_preparation', 'deadline_monitor',
    ];

    public function runsWorkflow(string $key): bool
    {
        $enabled = $this->enabled_workflows;

        if ($enabled === null) {
            return in_array($key, self::CORE_WORKFLOWS, true);
        }

        return in_array($key, $enabled, true);
    }

    /** @return BelongsTo<User, $this> */
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
