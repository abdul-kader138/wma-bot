<?php

namespace App\Services\Maria\Google;

use App\Models\Setting;

class GoogleConfiguration
{
    public function clientId(): ?string
    {
        return Setting::get('google_client_id') ?: config('services.google.client_id');
    }

    public function clientSecret(): ?string
    {
        return Setting::getSecret('google_client_secret') ?: config('services.google.client_secret');
    }

    public function redirectUri(): ?string
    {
        return Setting::get('google_redirect_uri') ?: config('services.google.redirect_uri');
    }
}
