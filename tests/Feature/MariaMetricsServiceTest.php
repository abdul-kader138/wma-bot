<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkflowRun;
use App\Services\Maria\MariaMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MariaMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_aggregates_spend_success_rate_and_time_saved_per_workflow(): void
    {
        Setting::set('claude_price_input_per_mtok', 2.0, 'claude');
        Setting::set('claude_price_output_per_mtok', 10.0, 'claude');
        $user = User::factory()->create();

        $this->makeRun($user, 'morning_brief', 'completed', 1_000_000, 200_000);
        $this->makeRun($user, 'morning_brief', 'failed', 0, 0);
        $this->makeRun($user, 'email_triage', 'completed', 500_000, 100_000, estimatedManualMinutes: 30);
        // Outside the window — must be excluded.
        $this->makeRun($user, 'morning_brief', 'completed', 9_000_000, 9_000_000, daysAgo: 90);

        $summary = app(MariaMetricsService::class)->summary(30);

        $this->assertSame(3, $summary['totals']['runs']);
        $this->assertSame(2, $summary['totals']['completed']);
        $this->assertSame(1, $summary['totals']['failed']);
        // input 1.5M * $2 + output 0.3M * $10 = 3 + 3 = 6.00
        $this->assertEqualsWithDelta(6.0, $summary['totals']['cost'], 0.001);
        $this->assertSame(66.7, $summary['totals']['success_rate']);

        $morning = collect($summary['workflows'])->firstWhere('workflow', 'morning_brief');
        $this->assertSame(50.0, $morning['success_rate']);
        $this->assertSame(20, $morning['est_minutes_saved']); // one completed run, default 20 min
        $triage = collect($summary['workflows'])->firstWhere('workflow', 'email_triage');
        $this->assertSame(30, $triage['est_minutes_saved']); // uses the run's own estimate
    }

    public function test_it_includes_chat_token_spend_from_the_audit_trail(): void
    {
        $user = User::factory()->create();
        AuditLog::create([
            'category' => 'maria_chat', 'action' => 'message', 'owner_id' => $user->id,
            'metadata' => ['input_tokens' => 400_000, 'output_tokens' => 50_000], 'created_at' => now(),
        ]);
        Setting::set('claude_price_input_per_mtok', 1.0, 'claude');
        Setting::set('claude_price_output_per_mtok', 5.0, 'claude');

        $summary = app(MariaMetricsService::class)->summary(7);

        $this->assertSame(1, $summary['chat']['turns']);
        // 0.4M*1 + 0.05M*5 = 0.4 + 0.25 = 0.65
        $this->assertEqualsWithDelta(0.65, $summary['chat']['cost'], 0.001);
        $this->assertEqualsWithDelta(0.65, $summary['totals']['cost'], 0.001);
    }

    public function test_it_can_scope_to_a_single_owner(): void
    {
        $mine = User::factory()->create();
        $other = User::factory()->create();
        $this->makeRun($mine, 'morning_brief', 'completed', 100, 100);
        $this->makeRun($other, 'morning_brief', 'completed', 100, 100);

        $this->assertSame(1, app(MariaMetricsService::class)->summary(30, $mine->id)['totals']['runs']);
        $this->assertSame(2, app(MariaMetricsService::class)->summary(30)['totals']['runs']);
    }

    private function makeRun(User $user, string $type, string $status, int $in, int $out, int $daysAgo = 1, int $estimatedManualMinutes = 0): void
    {
        $run = WorkflowRun::create([
            'run_id' => (string) Str::uuid(), 'user_id' => $user->id, 'workflow_type' => $type,
            'status' => $status, 'input_tokens' => $in, 'output_tokens' => $out,
            'estimated_manual_minutes' => $estimatedManualMinutes,
            'error' => $status === 'failed' ? 'boom' : null,
            'started_at' => now()->subDays($daysAgo),
        ]);
        $run->forceFill(['created_at' => now()->subDays($daysAgo)])->saveQuietly();
    }
}
