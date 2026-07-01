<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BotJobFailedNotification extends Notification
{
    use Queueable;

    public function __construct(private string $error, private array $payload) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('WMA Bot: a WhatsApp message failed to process')
            ->line('The bot gave up processing an incoming WhatsApp message after exhausting its retries.')
            ->line("Error: {$this->error}")
            ->line('Payload: '.json_encode($this->payload));
    }
}
