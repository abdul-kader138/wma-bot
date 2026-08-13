<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Claim extends Model
{
    protected $fillable = [
        'user_id', 'claim_text', 'subject', 'category', 'source_url',
        'verified_at', 'recheck_at', 'permitted_brands', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'recheck_at' => 'datetime',
            'permitted_brands' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
