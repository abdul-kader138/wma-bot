<?php

namespace App\Notifications;

use App\Models\AssistantBrief;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A short email nudge that a brief is ready, so an owner who does not open the
 * app still gets the day's priorities. Sent only when the digest setting is on.
 */
class MariaBriefReadyNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly AssistantBrief $brief,
        private readonly int $pendingApprovals = 0,
        private readonly int $activeAlerts = 0,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $content = (array) $this->brief->content;
        $date = $this->brief->brief_date?->toFormattedDateString();
        $isMorning = $this->brief->type === 'morning';

        $mail = (new MailMessage)
            ->subject(($isMorning ? 'Morning Command Brief' : 'Evening Review').($date ? " — {$date}" : ''))
            ->line($isMorning ? "Today's priorities:" : 'End-of-day summary:');

        if ($isMorning) {
            foreach (array_slice($content['outcomes'] ?? [], 0, 3) as $i => $outcome) {
                $mail->line(($i + 1).'. '.($outcome['title'] ?? '(untitled)'));
            }
            if ($risk = ($content['risk']['title'] ?? null)) {
                $mail->line("Risk to watch: {$risk}");
            }
        } else {
            $mail->line('Awaiting your approval: '.count($content['awaiting_approval'] ?? []));
            foreach (array_slice($content['tomorrow_top_three'] ?? [], 0, 3) as $item) {
                $mail->line('Tomorrow: '.(is_array($item) ? ($item['title'] ?? json_encode($item)) : $item));
            }
        }

        $mail->line("Pending approvals: {$this->pendingApprovals} · Active alerts: {$this->activeAlerts}")
            ->action('Open the Command Center', url('/admin/maria-dashboard'))
            ->line('This is a summary — open the app for the full brief and to act on anything.');

        return $mail;
    }
}
