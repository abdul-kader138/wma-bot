<?php

namespace App\Http\Controllers;

class InstagramWebhookController extends MetaWebhookController
{
    protected function platform(): string
    {
        return 'instagram';
    }

    protected function verifyTokenSettingKey(): string
    {
        return 'instagram_verify_token';
    }

    protected function configKey(): string
    {
        return 'instagram';
    }
}
