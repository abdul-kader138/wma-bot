<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Service extends Model
{
    protected $fillable = [
        'slug', 'label', 'prompt_label', 'color', 'icon',
        'is_active', 'sort_order', 'tool_name', 'tool_description', 'tool_fields',
    ];

    protected $casts = [
        'label'       => 'array',
        'tool_fields' => 'array',
        'is_active'   => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('services:all'));
        static::deleted(fn () => Cache::forget('services:all'));
    }

    // Returns all active services keyed by slug, same shape as config('services_bot.services')
    public static function toConfig(): array
    {
        return Cache::rememberForever('services:all', function () {
            return static::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->keyBy('slug')
                ->map(fn (Service $s) => [
                    'label'        => $s->label,
                    'prompt_label' => $s->prompt_label ?? '',
                    'tool' => [
                        'name'        => $s->tool_name ?? '',
                        'description' => $s->tool_description ?? '',
                        'fields'      => collect($s->tool_fields ?? [])
                            ->keyBy('name')
                            ->map(fn ($f) => [
                                'type'        => $f['type'] ?? 'string',
                                'required'    => (bool) ($f['required'] ?? false),
                                'description' => $f['description'] ?? '',
                            ])
                            ->toArray(),
                    ],
                ])
                ->toArray();
        });
    }

    // Simple slug => label[locale] map for dropdown options
    public static function options(string $locale = 'en'): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (Service $s) => [
                $s->slug => $s->label[$locale] ?? $s->label['en'] ?? $s->slug,
            ])
            ->toArray();
    }

    public function getLabelForLocale(string $locale = 'en'): string
    {
        return $this->label[$locale] ?? $this->label['en'] ?? $this->slug;
    }
}
