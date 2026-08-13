<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Communication extends Model
{
    protected $fillable = [
        'user_id', 'contact_id', 'connector_account_id', 'thread_source_id',
        'message_source_id', 'channel', 'direction', 'classification', 'summary',
        'commitments', 'draft_response', 'sensitivity', 'follow_up_at', 'source_url',
        'source_metadata',
    ];

    protected function casts(): array
    {
        return ['commitments' => 'array', 'source_metadata' => 'array', 'follow_up_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(MariaContact::class);
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(ConnectorAccount::class, 'connector_account_id');
    }
}
