<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantChannelIdentity extends Model
{
    protected $fillable = [
        'assistant_profile_id', 'whatsapp_account_id', 'platform',
        'external_identifier', 'label', 'is_active', 'verified_at',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'verified_at' => 'datetime'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(AssistantProfile::class, 'assistant_profile_id');
    }

    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }

    public function scopeAuthorized(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereNotNull('verified_at')
            ->whereHas('profile', fn (Builder $profile) => $profile->where('is_active', true));
    }

    public static function resolveAuthorized(
        string $platform,
        string $externalIdentifier,
        ?int $channelAccountId = null,
    ): ?self {
        return static::authorized()
            ->where('platform', $platform)
            ->where('external_identifier', $externalIdentifier)
            ->where('whatsapp_account_id', $channelAccountId)
            ->first();
    }
}
