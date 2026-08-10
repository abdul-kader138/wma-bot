<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaudeDailyUsage extends Model
{
    protected $fillable = ['whatsapp_account_id', 'phone', 'platform', 'date', 'count'];

    protected $casts = [
        'date'  => 'date',
        'count' => 'integer',
    ];

    public function whatsAppAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class);
    }
}
