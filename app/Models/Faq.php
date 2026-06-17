<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = ['service', 'question', 'keywords', 'answer', 'is_active'];

    protected $casts = [
        'keywords'  => 'array',
        'answer'    => 'array',
        'is_active' => 'boolean',
    ];

    public function answerFor(string $lang): string
    {
        return $this->answer[$lang] ?? $this->answer['en'] ?? '';
    }
}
