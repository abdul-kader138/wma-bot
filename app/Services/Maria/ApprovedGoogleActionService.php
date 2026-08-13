<?php

namespace App\Services\Maria;

use App\Models\Approval;
use App\Models\AssistantAction;
use App\Models\ConnectorAccount;
use App\Models\User;
use App\Services\Maria\Google\GmailWriteClient;
use App\Services\Maria\Google\GoogleCalendarWriteClient;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApprovedGoogleActionService
{
    public function __construct(
        private readonly ApprovalService $approvals,
        private readonly AssistantActionService $actions,
        private readonly GmailWriteClient $gmail,
        private readonly GoogleCalendarWriteClient $calendar,
    ) {}

    public function requestEmail(User $owner, array $input): Approval
    {
        $data = Validator::make($input, [
            'connector_account_id' => ['required', 'integer'], 'to' => ['required', 'email:rfc', 'not_regex:/[\r\n]/'],
            'cc' => ['nullable', 'string', 'max:1000', 'not_regex:/[\r\n]/'], 'bcc' => ['nullable', 'string', 'max:1000', 'not_regex:/[\r\n]/'],
            'subject' => ['required', 'string', 'max:998', 'not_regex:/[\r\n]/'], 'body' => ['required', 'string', 'max:100000'],
            'thread_id' => ['nullable', 'string', 'max:255'],
        ])->validate();
        $connector = $this->ownedConnector($owner, (int) $data['connector_account_id']);
        $this->assertScope($connector, GmailWriteClient::SCOPE, 'Gmail send');
        $data = array_merge(['cc' => null, 'bcc' => null, 'thread_id' => null], $data);

        return $this->approvals->request(
            $owner, 'google_email_send', "Send email to {$data['to']}: {$data['subject']}", $data,
            preview: "To: {$data['to']}\nSubject: {$data['subject']}\n\n{$data['body']}", recipientChannel: $data['to'], riskLevel: 'medium',
        );
    }

    public function requestCalendarEvent(User $owner, array $input): Approval
    {
        $data = Validator::make($input, [
            'connector_account_id' => ['required', 'integer'], 'title' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'], 'location' => ['nullable', 'string', 'max:1000'],
            'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'],
            'timezone' => ['required', 'timezone'], 'attendees' => ['array', 'max:50'], 'attendees.*' => ['email:rfc'],
        ])->validate();
        $connector = $this->ownedConnector($owner, (int) $data['connector_account_id']);
        $this->assertScope($connector, GoogleCalendarWriteClient::SCOPE, 'Calendar write');
        $data = array_merge(['description' => null, 'location' => null, 'attendees' => []], $data);

        return $this->approvals->request(
            $owner, 'google_calendar_create', "Create calendar event: {$data['title']}", $data,
            preview: "{$data['title']}\n{$data['starts_at']} – {$data['ends_at']} ({$data['timezone']})\nAttendees: ".implode(', ', $data['attendees']),
            recipientChannel: 'Google Calendar', riskLevel: 'medium',
        );
    }

    public function execute(Approval $approval, User $actor): AssistantAction
    {
        if ($approval->user_id !== $actor->id && ! $actor->isAdmin()) {
            throw ValidationException::withMessages(['approval' => 'You cannot execute this approval.']);
        }
        if (! in_array($approval->action_type, ['google_email_send', 'google_calendar_create'], true)) {
            throw ValidationException::withMessages(['approval' => 'This is not a supported Google write approval.']);
        }

        $input = $approval->proposed_content;
        $owner = $approval->user;
        $connector = $this->ownedConnector($owner, (int) $input['connector_account_id']);
        $action = $this->actions->reserve($owner, $approval->action_type, $input, $approval->workflowRun, $approval);
        if ($action->status === 'completed') {
            return $action;
        }
        $action = $this->actions->markExecuting($action);

        try {
            if ($approval->action_type === 'google_email_send') {
                $result = $this->gmail->send($connector, $input, $action->idempotency_key);

                return $this->actions->markCompleted($action, (string) $result['id'], ['message_id' => $result['id'], 'thread_id' => $result['threadId'] ?? null]);
            }

            $result = $this->calendar->createEvent($connector, $input, substr($action->idempotency_key, 0, 32));

            return $this->actions->markCompleted($action, (string) $result['id'], ['event_id' => $result['id'], 'html_link' => $result['htmlLink'] ?? null]);
        } catch (Throwable $error) {
            $this->actions->markFailed($action, mb_substr($error->getMessage(), 0, 5000));
            throw $error;
        }
    }

    private function ownedConnector(User $owner, int $id): ConnectorAccount
    {
        return ConnectorAccount::whereKey($id)->where('user_id', $owner->id)->where('provider', 'google')->where('status', 'active')->firstOrFail();
    }

    private function assertScope(ConnectorAccount $connector, string $scope, string $label): void
    {
        if (! in_array($scope, $connector->scopes ?? [], true)) {
            throw ValidationException::withMessages(['connector' => "{$label} permission is not granted. Use Enable approved writes first."]);
        }
    }
}
