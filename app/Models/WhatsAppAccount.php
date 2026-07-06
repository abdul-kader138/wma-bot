<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppAccount extends Model
{
    protected $table = 'whatsapp_accounts';

    protected $fillable = [
        'name',
        'phone_number_id',
        'waba_id',
        'access_token',
        'api_version',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
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

    protected static function booted(): void
    {
        static::saved(function (self $account) {
            if ($account->is_default) {
                static::where('id', '!=', $account->id)->update(['is_default' => false]);
            }
        });
    }
}
