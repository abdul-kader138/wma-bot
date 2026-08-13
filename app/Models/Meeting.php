<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meeting extends Model
{
    protected $fillable = [
        'user_id', 'connector_account_id', 'calendar_event_id', 'title', 'starts_at',
        'ends_at', 'attendees', 'domain', 'tier', 'objective', 'preparation_status',
        'brief', 'notes_source', 'decisions', 'action_items', 'thank_you_draft',
        'follow_up_at', 'confidentiality',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime', 'ends_at' => 'datetime', 'follow_up_at' => 'datetime',
            'attendees' => 'array', 'brief' => 'array', 'decisions' => 'array', 'action_items' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(ConnectorAccount::class, 'connector_account_id');
    }
}
