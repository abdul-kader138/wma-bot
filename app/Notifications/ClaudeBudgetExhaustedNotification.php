<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaudeBudgetExhaustedNotification extends Notification
{
    use Queueable;

    public function __construct(private int $dailyCap) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('WMA Bot: daily Claude budget exhausted')
            ->line("The bot has hit its configured daily cap of {$this->dailyCap} Claude calls, shared across every WhatsApp account and customer.")
            ->line('Customers are now getting the fallback message instead of AI replies until the cap resets at midnight.')
            ->line('Raise "Daily Claude Call Cap" in System Settings → Claude AI if this is expected traffic, not abuse.');
    }
}
