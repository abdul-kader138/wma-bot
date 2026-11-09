<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class CatalogueEntry extends Model
{
    protected $fillable = [
        'whatsapp_account_id', 'service_id', 'catalogue_category_id',
        'name', 'description', 'price_minor_units', 'currency',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn (CatalogueEntry $e) => static::forgetCacheForAccount($e->whatsapp_account_id));
        static::deleted(fn (CatalogueEntry $e) => static::forgetCacheForAccount($e->whatsapp_account_id));
    }

    public function whatsAppAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CatalogueCategory::class, 'catalogue_category_id');
    }

    public static function forgetCacheForAccount(?int $whatsappAccountId): void
    {
        Cache::forget("catalogue:all:{$whatsappAccountId}");
    }

    // All active entries for one account, cached — filtered per-service in memory by
    // listForService() below rather than cached per-service, since a category/entry
    // edit can't cheaply know every service it affects to invalidate those keys too.
    private static function allForAccount(?int $whatsappAccountId): Collection
    {
        return Cache::rememberForever("catalogue:all:{$whatsappAccountId}", function () use ($whatsappAccountId) {
            return static::where('whatsapp_account_id', $whatsappAccountId)
                ->where('is_active', true)
                ->with('category')
                ->orderBy('sort_order')
                ->get();
        });
    }

    // Entries visible under one service's "menu"/"catalogue" keyword: tied to that
    // service directly, or left unassigned (service_id null) to show under every
    // catalogue-enabled service on the account — same shared/scoped fallback shape
    // KnowledgeChunk::scopeForAccount() already uses for documents.
    public static function listForService(?int $whatsappAccountId, int $serviceId): Collection
    {
        return static::allForAccount($whatsappAccountId)
            ->filter(fn (CatalogueEntry $e) => $e->service_id === null || $e->service_id === $serviceId)
            ->sortBy([
                fn ($e) => $e->category->sort_order ?? 0,
                fn ($e) => $e->sort_order,
            ])
            ->values();
    }

    public function getNameForLocale(string $locale = 'en'): string
    {
        return $this->name[$locale] ?? $this->name['en'] ?? '';
    }

    public function getDescriptionForLocale(string $locale = 'en'): ?string
    {
        return $this->description[$locale] ?? $this->description['en'] ?? null;
    }

    public function formattedPrice(): ?string
    {
        if ($this->price_minor_units === null || ! $this->currency) {
            return null;
        }

        return number_format($this->price_minor_units / 100, 2).' '.$this->currency;
    }
}
