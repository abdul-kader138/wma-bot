<?php

namespace App\Notifications;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConversationEscalatedNotification extends Notification
{
    use Queueable;

    public function __construct(private Conversation $conversation, private bool $afterHours = false) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $c = $this->conversation;
        $subject = "Customer asked for a human — {$c->wa_phone}";
        if ($this->afterHours) {
            $subject .= ' (outside business hours)';
        }

        $mail = (new MailMessage)
            ->subject($subject)
            ->line('A customer asked to talk to a human and the bot has paused for this conversation.')
            ->line("Contact: {$c->wa_phone}")
            ->line('Service: '.($c->service ?? '—'));

        if ($this->afterHours) {
            $mail->line('This came in outside business hours — the customer was told someone will respond when the team is back.');
        }

        return $mail->action('Open conversation', url('/admin/conversations/'.$c->id));
    }
}
