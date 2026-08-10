<?php

namespace App\Http\Controllers;

class MessengerWebhookController extends MetaWebhookController
{
    protected function platform(): string
    {
        return 'messenger';
    }

    protected function verifyTokenSettingKey(): string
    {
        return 'messenger_verify_token';
    }

    protected function configKey(): string
    {
        return 'messenger';
    }
}
