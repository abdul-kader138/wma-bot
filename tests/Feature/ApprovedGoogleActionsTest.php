<?php

namespace Tests\Feature;

use App\Models\ConnectorAccount;
use App\Models\User;
use App\Services\Maria\ApprovalService;
use App\Services\Maria\ApprovedGoogleActionService;
use App\Services\Maria\Google\GmailWriteClient;
use App\Services\Maria\Google\GoogleCalendarWriteClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApprovedGoogleActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_requires_exact_approval_and_executes_only_once(): void
    {
        $user = User::factory()->create();
        $connector = $this->connector($user, [GmailWriteClient::SCOPE]);
        $service = app(ApprovedGoogleActionService::class);
        $approval = $service->requestEmail($user, [
            'connector_account_id' => $connector->id, 'to' => 'recipient@example.com',
            'subject' => 'Approved subject', 'body' => 'Approved body',
        ]);
        app(ApprovalService::class)->approve($approval, $user, $approval->proposed_content);
        Http::fake(['https://gmail.googleapis.com/*' => Http::response(['id' => 'message-1', 'threadId' => 'thread-1'])]);

        $first = $service->execute($approval->refresh(), $user);
        $second = $service->execute($approval->refresh(), $user);

        $this->assertTrue($first->is($second));
        $this->assertSame('completed', $second->status);
        $this->assertSame('message-1', $second->provider_confirmation_id);
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $raw = base64_decode(strtr($request['raw'], '-_', '+/'));

            return str_contains($raw, 'Subject: Approved subject') && str_contains($raw, 'Approved body');
        });
    }

    public function test_calendar_event_uses_deterministic_provider_id(): void
    {
        $user = User::factory()->create();
        $connector = $this->connector($user, [GoogleCalendarWriteClient::SCOPE]);
        $service = app(ApprovedGoogleActionService::class);
        $approval = $service->requestCalendarEvent($user, [
            'connector_account_id' => $connector->id, 'title' => 'Strategy meeting',
            'starts_at' => '2026-08-20T10:00:00+02:00', 'ends_at' => '2026-08-20T11:00:00+02:00',
            'timezone' => 'Europe/Berlin', 'attendees' => ['guest@example.com'],
        ]);
        app(ApprovalService::class)->approve($approval, $user, $approval->proposed_content);
        Http::fake(['https://www.googleapis.com/calendar/*' => Http::response(['id' => 'event-1', 'htmlLink' => 'https://calendar.test/event-1'])]);

        $action = $service->execute($approval->refresh(), $user);

        $this->assertSame('event-1', $action->provider_confirmation_id);
        Http::assertSent(fn ($request) => $request['id'] === substr($action->idempotency_key, 0, 32)
            && $request['attendees'][0]['email'] === 'guest@example.com');
    }

    public function test_read_only_connector_cannot_create_write_approval(): void
    {
        $user = User::factory()->create();
        $connector = $this->connector($user, ['https://www.googleapis.com/auth/gmail.readonly']);

        $this->expectException(ValidationException::class);
        app(ApprovedGoogleActionService::class)->requestEmail($user, [
            'connector_account_id' => $connector->id, 'to' => 'recipient@example.com', 'subject' => 'Subject', 'body' => 'Body',
        ]);
    }

    public function test_two_separate_approvals_for_identical_content_are_distinct_actions(): void
    {
        $user = User::factory()->create();
        $connector = $this->connector($user, [GmailWriteClient::SCOPE]);
        $service = app(ApprovedGoogleActionService::class);
        $input = ['connector_account_id' => $connector->id, 'to' => 'recipient@example.com', 'subject' => 'Same', 'body' => 'Same body'];
        $firstApproval = $service->requestEmail($user, $input);
        $secondApproval = $service->requestEmail($user, $input);
        app(ApprovalService::class)->approve($firstApproval, $user, $firstApproval->proposed_content);
        app(ApprovalService::class)->approve($secondApproval, $user, $secondApproval->proposed_content);
        Http::fake(['https://gmail.googleapis.com/*' => Http::sequence()->push(['id' => 'message-1'])->push(['id' => 'message-2'])]);

        $first = $service->execute($firstApproval->refresh(), $user);
        $second = $service->execute($secondApproval->refresh(), $user);

        $this->assertNotSame($first->idempotency_key, $second->idempotency_key);
        $this->assertDatabaseCount('assistant_actions', 2);
        Http::assertSentCount(2);
    }

    private function connector(User $user, array $scopes): ConnectorAccount
    {
        return ConnectorAccount::create([
            'user_id' => $user->id, 'provider' => 'google', 'provider_account_id' => 'google-'.$user->id,
            'email' => $user->email, 'access_token' => 'valid-token', 'refresh_token' => 'refresh-token',
            'token_expires_at' => now()->addHour(), 'scopes' => $scopes, 'status' => 'active',
        ]);
    }
}
