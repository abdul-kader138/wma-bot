<?php

namespace Tests\Unit\Maria;

use App\Jobs\Maria\GenerateMorningBrief;
use App\Models\Setting;
use App\Notifications\MariaWorkflowFailedNotification;
use App\Services\Maria\ClaudeRateLimited;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class WorkflowFailureAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_hard_workflow_failure_emails_staff(): void
    {
        Notification::fake();
        Setting::set('staff_notification_email', 'ops@example.com', 'email');

        (new GenerateMorningBrief(1, '2026-09-07'))->failed(new RuntimeException('schema validation blew up'));

        Notification::assertSentOnDemand(
            MariaWorkflowFailedNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'ops@example.com'
        );
    }

    public function test_a_transient_rate_limit_failure_is_not_emailed(): void
    {
        Notification::fake();
        Setting::set('staff_notification_email', 'ops@example.com', 'email');

        (new GenerateMorningBrief(1, '2026-09-07'))->failed(new ClaudeRateLimited(120));

        Notification::assertNothingSent();
    }

    public function test_nothing_is_sent_when_no_staff_email_is_configured(): void
    {
        Notification::fake();

        (new GenerateMorningBrief(1, '2026-09-07'))->failed(new RuntimeException('boom'));

        Notification::assertNothingSent();
    }
}
