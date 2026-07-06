<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    protected $fillable = ['whatsapp_account_id', 'wa_phone', 'step', 'language', 'service', 'history'];

    protected $casts = ['history' => 'array'];

    public function whatsAppAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }

    /**
     * Not an Eloquent relation: ServiceRequest is scoped by (wa_phone, whatsapp_account_id),
     * a composite key hasMany() can't express safely for eager loading (with() would reuse
     * one conversation's account id across the whole batch). Query directly instead.
     */
    public function serviceRequests(): Builder
    {
        return ServiceRequest::query()
            ->where('wa_phone', $this->wa_phone)
            ->where('whatsapp_account_id', $this->whatsapp_account_id);
    }
}
