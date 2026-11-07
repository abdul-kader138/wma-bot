<?php

namespace App\Notifications;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConversationEscalatedNotification extends Notification
{
    use Queueable;

    public function __construct(private Conversation $conversation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $c = $this->conversation;

        return (new MailMessage)
            ->subject("Customer asked for a human — {$c->wa_phone}")
            ->line('A customer asked to talk to a human and the bot has paused for this conversation.')
            ->line("Contact: {$c->wa_phone}")
            ->line('Service: '.($c->service ?? '—'))
            ->action('Open conversation', url('/admin/conversations/'.$c->id));
    }
}
