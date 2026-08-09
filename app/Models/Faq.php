<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    protected $fillable = ['whatsapp_account_id', 'service', 'question', 'keywords', 'answer', 'is_active'];

    protected $casts = [
        'question'  => 'array',
        'keywords'  => 'array',
        'answer'    => 'array',
        'is_active' => 'boolean',
    ];

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

    /** All non-empty question translations, e.g. for matching across languages. */
    public function questionVariants(): array
    {
        return array_values(array_filter($this->question ?? []));
    }
}
