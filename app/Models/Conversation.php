<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = ['wa_phone', 'step', 'language', 'service', 'history'];

    protected $casts = ['history' => 'array'];

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'wa_phone', 'wa_phone');
    }
}
