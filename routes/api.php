<?php

use App\Http\Controllers\InstagramWebhookController;
use App\Http\Controllers\MessengerWebhookController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// 120 requests/minute is generous headroom above real traffic for a single business
// account, while still blocking a flood aimed directly at these public URLs.
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/webhook/whatsapp',  [WhatsAppWebhookController::class, 'verify']);
    Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle']);

    Route::get('/webhook/messenger',  [MessengerWebhookController::class, 'verify']);
    Route::post('/webhook/messenger', [MessengerWebhookController::class, 'handle']);

    Route::get('/webhook/instagram',  [InstagramWebhookController::class, 'verify']);
    Route::post('/webhook/instagram', [InstagramWebhookController::class, 'handle']);
});
