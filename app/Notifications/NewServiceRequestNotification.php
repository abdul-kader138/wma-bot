<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewServiceRequestNotification extends Notification
{
    use Queueable;

    public function __construct(private ServiceRequest $serviceRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->serviceRequest;

        $mail = (new MailMessage)
            ->subject("New {$r->service} request from {$r->wa_phone}")
            ->line("A new WhatsApp service request has come in.")
            ->line("Service: {$r->service}")
            ->line("Phone: {$r->wa_phone}");

        foreach ($r->payload ?? [] as $field => $value) {
            $mail->line(ucfirst(str_replace('_', ' ', $field)).': '.(is_array($value) ? json_encode($value) : $value));
        }

        return $mail->action('Open in admin panel', url('/admin/service-requests/'.$r->id.'/edit'));
    }
}
