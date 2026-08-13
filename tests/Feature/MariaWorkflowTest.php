<?php

namespace Tests\Feature;

use App\Models\AssistantProfile;
use App\Models\Communication;
use App\Models\ConnectorAccount;
use App\Models\MariaTask;
use App\Models\User;
use App\Services\Maria\EmailTriageService;
use App\Services\Maria\MorningBriefService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MariaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_morning_brief_records_source_gaps_and_is_idempotent_per_day(): void
    {
        $user = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $user->id, 'timezone' => 'Europe/Berlin']);
        MariaTask::create(['user_id' => $user->id, 'task' => 'Prepare meeting', 'owner_name' => $user->name, 'due_at' => now()->addHour()]);
        Http::fake(['https://api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'tool_use', 'name' => 'save_morning_brief', 'id' => 'brief-1', 'input' => [
                'date' => now('Europe/Berlin')->toDateString(),
                'outcomes' => [['title' => 'Prepare meeting', 'reason' => 'Due soon', 'owner' => $user->name, 'next_action' => 'Review notes']],
                'meetings' => [], 'approvals' => [], 'risk' => ['title' => 'None'],
                'source_gaps' => ['gmail_not_connected', 'calendar_not_connected'], 'status' => 'Completed',
            ]]], 'usage' => ['input_tokens' => 50, 'output_tokens' => 20],
        ])]);

        $first = app(MorningBriefService::class)->generate($profile);
        $second = app(MorningBriefService::class)->generate($profile);

        $this->assertTrue($first->is($second));
        $this->assertDatabaseCount('assistant_briefs', 1);
        $this->assertSame(['gmail_not_connected', 'calendar_not_connected'], $first->workflowRun->source_gaps);
        $this->assertSame('completed', $first->workflowRun->status);
        Http::assertSentCount(1);
    }

    public function test_email_triage_persists_summary_not_full_body(): void
    {
        $user = User::factory()->create();
        $connector = ConnectorAccount::create([
            'user_id' => $user->id, 'provider' => 'google', 'provider_account_id' => 'g1',
            'access_token' => 'access', 'refresh_token' => 'refresh',
            'token_expires_at' => now()->addHour(), 'status' => 'active',
        ]);
        Http::fake([
            'https://gmail.googleapis.com/gmail/v1/users/me/messages?*' => Http::response(['messages' => [['id' => 'm1']]]),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/m1*' => Http::response([
                'id' => 'm1', 'threadId' => 't1', 'snippet' => 'Please send the document by Friday',
                'payload' => ['headers' => [['name' => 'From', 'value' => 'Maria <m@example.com>'], ['name' => 'Subject', 'value' => 'Document']]],
            ]),
            'https://api.anthropic.com/*' => Http::response([
                'content' => [[
                    'type' => 'tool_use', 'name' => 'save_email_triage', 'id' => 'triage-1',
                    'input' => ['messages' => [[
                        'id' => 'm1', 'classification' => 'Act Today', 'summary' => 'Document requested by Friday.',
                        'commitments' => ['Send document by Friday'], 'draft_response' => 'Draft reply', 'sensitivity' => 'internal',
                    ]]],
                ]],
            ]),
        ]);

        $result = app(EmailTriageService::class)->triage($connector);

        $this->assertCount(1, $result);
        $communication = Communication::first();
        $this->assertSame('Document requested by Friday.', $communication->summary);
        $this->assertSame('Draft reply', $communication->draft_response);
        $this->assertArrayNotHasKey('body', $communication->source_metadata);
    }
}
