<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Faq extends Model
{
    protected $fillable = ['whatsapp_account_id', 'service', 'question', 'keywords', 'answer', 'is_active'];

    protected $casts = [
        'question' => 'array',
        'keywords' => 'array',
        'answer' => 'array',
        'is_active' => 'boolean',
    ];

    // Mirrors Service::toConfig()'s caching — FaqMatcher::candidates() reads
    // through this same key on every incoming message, so it must be busted
    // on every write, not just left to expire.
    protected static function booted(): void
    {
        static::saved(fn (Faq $f) => Cache::forget("faqs:active:{$f->whatsapp_account_id}"));
        static::deleted(fn (Faq $f) => Cache::forget("faqs:active:{$f->whatsapp_account_id}"));
    }

    public function answerFor(string $lang): string
    {
        return $this->answer[$lang] ?? $this->answer['en'] ?? '';
    }

    public function questionFor(string $lang): string
    {
        return $this->question[$lang] ?? $this->question['en'] ?? '';
    }

    public function whatsAppAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class);
    }

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(
            WhatsAppAccount::class,
            'faq_whatsapp_account',
            'faq_id',
            'whatsapp_account_id'
        );
    }

    public function scopeForAccount($query, ?int $accountId)
    {
        return $query->where(function ($query) use ($accountId) {
            $query->where('whatsapp_account_id', $accountId)
                ->orWhereHas('accounts', fn ($accounts) => $accounts->whereKey($accountId));
        });
    }

    /** All non-empty question translations, e.g. for matching across languages. */
    public function questionVariants(): array
    {
        return array_values(array_filter($this->question ?? []));
    }
}
