<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per free-text answer the bot sends during IN_SERVICE (FAQ hit, knowledge-base
 * hit, or neither), plus the 👍/👎 rating a customer taps afterwards. See
 * HandleIncomingMessage::logAnswerEvent() for where these are written.
 */
class BotAnswerEvent extends Model
{
    protected $fillable = [
        'whatsapp_account_id', 'conversation_id', 'wa_phone', 'platform', 'service', 'language',
        'question', 'answer', 'match_source', 'faq_id', 'chunk_count', 'top_distance', 'rating', 'rated_at',
    ];

    protected $casts = [
        'top_distance' => 'float',
        'rating' => 'integer',
        'rated_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
