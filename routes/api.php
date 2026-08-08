<?php

use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// 120 requests/minute is generous headroom above real WhatsApp traffic for a single
// business number, while still blocking a flood aimed directly at this public URL.
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/webhook/whatsapp',  [WhatsAppWebhookController::class, 'verify']);
    Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle']);
});
