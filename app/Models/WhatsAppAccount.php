<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Despite the name, this model backs every channel's accounts, not just WhatsApp —
 * see the `platform` column. Messenger and Instagram accounts are rows here with
 * platform='messenger'/'instagram' and external_id set to their Page ID / Instagram
 * Business Account ID (phone_number_id/waba_id stay null for those). Kept as one
 * table rather than per-platform tables so conversations, service requests, and the
 * usage dashboard don't fragment by channel — see HandleIncomingMessage's platform
 * parameter and MetaMessagingClient for how the rest of the send/receive path stays
 * channel-agnostic. Renaming this class to something platform-neutral is a larger,
 * separate refactor deliberately deferred until this is proven in production.
 */
class WhatsAppAccount extends Model
{
    protected $table = 'whatsapp_accounts';

    protected $fillable = [
        'name',
        'platform',
        'phone_number_id',
        'external_id',
        'waba_id',
        'access_token',
        'app_secret',
        'api_version',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'app_secret'   => 'encrypted',
            'is_active'    => 'boolean',
            'is_default'   => 'boolean',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'whatsapp_account_id');
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'whatsapp_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    protected static function booted(): void
    {
        static::saved(function (self $account) {
            if ($account->is_default) {
                // Scoped by platform: "default" is per-channel (one default WhatsApp
                // number, one default Messenger page, ...), not global — without this
                // scope, marking a Messenger account default would incorrectly clear
                // the WhatsApp account's default flag too.
                static::where('platform', $account->platform)
                    ->where('id', '!=', $account->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
