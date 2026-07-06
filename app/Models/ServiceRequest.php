<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequest extends Model
{
    protected $fillable = ['whatsapp_account_id', 'wa_phone', 'service', 'payload', 'status', 'staff_notes'];

    protected $casts = ['payload' => 'array'];

    public function whatsAppAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }
}
