<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantConversation extends Model
{
    protected $fillable = [
        'assistant_profile_id', 'assistant_channel_identity_id', 'status',
        'history', 'last_prompt_version', 'input_tokens', 'output_tokens',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return ['history' => 'array', 'last_message_at' => 'datetime'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(AssistantProfile::class, 'assistant_profile_id');
    }

    public function channelIdentity(): BelongsTo
    {
        return $this->belongsTo(AssistantChannelIdentity::class, 'assistant_channel_identity_id');
    }
}
