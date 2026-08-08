<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = ['service', 'question', 'keywords', 'answer', 'is_active'];

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

    /** All non-empty question translations, e.g. for matching across languages. */
    public function questionVariants(): array
    {
        return array_values(array_filter($this->question ?? []));
    }
}
