<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A durable fact the owner asked Maria to remember, or that Maria derived and
 * kept — standing preferences, key names, recurring context. Keyed per owner so
 * it survives individual conversations.
 */
class AssistantMemory extends Model
{
    protected $fillable = ['user_id', 'key', 'value', 'source'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
