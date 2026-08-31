<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MariaWorkflowFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $workflow,
        private readonly string $error,
        private readonly string $context,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Maria workflow failed: {$this->workflow}")
            ->line("The Maria workflow \"{$this->workflow}\" failed after exhausting its retries.")
            ->line("Context: {$this->context}")
            ->line("Error: {$this->error}")
            ->line('Check Workflow Runs in the admin panel for the full record.');
    }
}
