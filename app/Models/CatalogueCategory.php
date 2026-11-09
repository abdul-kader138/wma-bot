<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class CatalogueCategory extends Model
{
    protected $fillable = [
        'whatsapp_account_id', 'name', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn (CatalogueCategory $c) => CatalogueEntry::forgetCacheForAccount($c->whatsapp_account_id));
        static::deleted(fn (CatalogueCategory $c) => CatalogueEntry::forgetCacheForAccount($c->whatsapp_account_id));
    }

    public function whatsAppAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CatalogueEntry::class);
    }

    public function getNameForLocale(string $locale = 'en'): string
    {
        return $this->name[$locale] ?? $this->name['en'] ?? '';
    }
}
