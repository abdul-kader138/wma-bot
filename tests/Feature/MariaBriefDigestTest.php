<?php

namespace Tests\Feature;

use App\Models\AssistantBrief;
use App\Models\AssistantProfile;
use App\Models\MariaTask;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\MariaBriefReadyNotification;
use App\Services\Maria\MorningBriefService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MariaBriefDigestTest extends TestCase
{
    use RefreshDatabase;

    private function fakeBriefResponse(): void
    {
        Http::fake(['https://api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'tool_use', 'name' => 'save_morning_brief', 'id' => 'b1', 'input' => [
                'date' => now()->toDateString(),
                'outcomes' => [
                    ['title' => 'Confirm travel dates', 'reason' => 'Overdue', 'owner' => 'Owner', 'next_action' => 'Call today'],
                    ['title' => 'Chase the NDA', 'reason' => 'Blocked', 'owner' => 'Owner', 'next_action' => 'Email legal'],
                ],
                'meetings' => [], 'approvals' => [], 'risk' => ['title' => 'Two dependencies on one deadline'],
                'source_gaps' => ['gmail_not_connected', 'calendar_not_connected'], 'status' => 'Completed',
            ]]],
            'usage' => ['input_tokens' => 40, 'output_tokens' => 20],
        ])]);
    }

    public function test_no_digest_is_sent_when_the_setting_is_off(): void
    {
        Notification::fake();
        $this->fakeBriefResponse();
        $user = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $user->id, 'timezone' => 'Europe/Rome']);
        MariaTask::create(['user_id' => $user->id, 'task' => 'x', 'owner_name' => 'Owner', 'due_at' => now()]);

        app(MorningBriefService::class)->generate($profile);

        Notification::assertNothingSent();
    }

    public function test_digest_is_emailed_to_the_owner_when_enabled(): void
    {
        Notification::fake();
        Setting::set('maria_digest_email_enabled', true, 'maria');
        $this->fakeBriefResponse();
        $user = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $user->id, 'timezone' => 'Europe/Rome']);
        MariaTask::create(['user_id' => $user->id, 'task' => 'x', 'owner_name' => 'Owner', 'due_at' => now()]);

        app(MorningBriefService::class)->generate($profile);

        Notification::assertSentTo($user, MariaBriefReadyNotification::class);
    }

    public function test_the_digest_email_renders_the_outcomes(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $brief = new AssistantBrief([
            'type' => 'morning', 'brief_date' => now()->toDateString(),
            'content' => ['outcomes' => [['title' => 'Confirm travel dates']], 'risk' => ['title' => 'Deadline risk']],
        ]);

        $mail = (new MariaBriefReadyNotification($brief, 2, 1))->toMail($user);
        $rendered = implode("\n", $mail->introLines);

        $this->assertStringContainsString('Confirm travel dates', $rendered);
        $this->assertStringContainsString('Risk to watch: Deadline risk', $rendered);
        $this->assertStringContainsString('Pending approvals: 2', $rendered);
    }
}
