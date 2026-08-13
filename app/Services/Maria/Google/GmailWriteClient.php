<?php

namespace App\Services\Maria\Google;

use App\Models\ConnectorAccount;
use Illuminate\Validation\ValidationException;

class GmailWriteClient
{
    public const SCOPE = 'https://www.googleapis.com/auth/gmail.send';

    public function __construct(private readonly GoogleWorkspaceClient $google) {}

    public function send(ConnectorAccount $connector, array $message, string $messageId): array
    {
        $this->assertScope($connector);
        $headers = ["Message-ID: <{$messageId}@maria.local>", 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8'];
        foreach (['to' => 'To', 'cc' => 'Cc', 'bcc' => 'Bcc', 'subject' => 'Subject'] as $key => $label) {
            if (filled($message[$key] ?? null)) {
                $headers[] = $label.': '.$message[$key];
            }
        }
        $raw = implode("\r\n", $headers)."\r\n\r\n".$message['body'];

        return $this->google->post($connector, 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
            'raw' => rtrim(strtr(base64_encode($raw), '+/', '-_'), '='),
            ...(filled($message['thread_id'] ?? null) ? ['threadId' => $message['thread_id']] : []),
        ]);
    }

    private function assertScope(ConnectorAccount $connector): void
    {
        if (! in_array(self::SCOPE, $connector->scopes ?? [], true)) {
            throw ValidationException::withMessages(['connector' => 'Reconnect Google using “Enable approved writes” to grant Gmail send permission.']);
        }
    }
}
