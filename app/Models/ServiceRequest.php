<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    protected $fillable = ['wa_phone', 'service', 'payload', 'status', 'staff_notes'];

    protected $casts = ['payload' => 'array'];
}
