<?php

namespace App\Services\Maria;

use App\Models\AssistantConversation;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\WorkflowRun;
use Illuminate\Support\Carbon;

/**
 * Aggregates Maria's operational metrics — spend, throughput, reliability, and
 * estimated time saved — from the records the workflows and chat already write.
 * Cost is derived from token counts and the configurable per-million-token
 * prices so it tracks whatever model is in use.
 */
class MariaMetricsService
{
    /** Rough minutes of manual work each workflow type replaces, when a run doesn't record its own estimate. */
    private const DEFAULT_MANUAL_MINUTES = [
        'morning_brief' => 20, 'evening_review' => 10, 'email_triage' => 15, 'meeting_preparation' => 25,
        'meeting_closeout' => 20, 'deadline_monitor' => 5, 'daily_five_relationships' => 40,
        'book_portfolio_review' => 30, 'agverse_opportunity_review' => 30, 'acm_weekly_production' => 45,
        'quality_report' => 20,
    ];

    /** @return array<string,mixed> */
    public function summary(int $days = 30, ?int $userId = null): array
    {
        $days = max(1, min(365, $days));
        $since = Carbon::now()->subDays($days);
        [$inPrice, $outPrice] = $this->prices();

        $runs = WorkflowRun::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->where('created_at', '>=', $since)
            ->get(['workflow_type', 'status', 'input_tokens', 'output_tokens', 'estimated_manual_minutes', 'verified_time_saved_minutes']);

        $byType = [];
        foreach ($runs as $run) {
            $type = $run->workflow_type;
            $row = $byType[$type] ??= [
                'workflow' => $type, 'runs' => 0, 'completed' => 0, 'failed' => 0,
                'input_tokens' => 0, 'output_tokens' => 0, 'cost' => 0.0,
                'est_minutes_saved' => 0, 'verified_minutes_saved' => 0,
            ];
            $row['runs']++;
            $row['completed'] += $run->status === 'completed' ? 1 : 0;
            $row['failed'] += $run->status === 'failed' ? 1 : 0;
            $row['input_tokens'] += (int) $run->input_tokens;
            $row['output_tokens'] += (int) $run->output_tokens;
            $row['cost'] += $this->cost((int) $run->input_tokens, (int) $run->output_tokens, $inPrice, $outPrice);
            $row['est_minutes_saved'] += $run->status === 'completed'
                ? ((int) $run->estimated_manual_minutes ?: (self::DEFAULT_MANUAL_MINUTES[$type] ?? 10))
                : 0;
            $row['verified_minutes_saved'] += (int) $run->verified_time_saved_minutes;
            $byType[$type] = $row;
        }

        foreach ($byType as &$row) {
            // Every grouped row has at least one run.
            $row['success_rate'] = round($row['completed'] / $row['runs'] * 100, 1);
            $row['cost'] = round($row['cost'], 4);
        }
        unset($row);
        uasort($byType, fn ($a, $b) => $b['cost'] <=> $a['cost']);

        $chat = $this->chat($since, $userId, $inPrice, $outPrice);

        $totals = [
            'runs' => array_sum(array_column($byType, 'runs')),
            'completed' => array_sum(array_column($byType, 'completed')),
            'failed' => array_sum(array_column($byType, 'failed')),
            'input_tokens' => array_sum(array_column($byType, 'input_tokens')) + $chat['input_tokens'],
            'output_tokens' => array_sum(array_column($byType, 'output_tokens')) + $chat['output_tokens'],
            'cost' => round(array_sum(array_column($byType, 'cost')) + $chat['cost'], 4),
            'est_minutes_saved' => array_sum(array_column($byType, 'est_minutes_saved')),
            'verified_minutes_saved' => array_sum(array_column($byType, 'verified_minutes_saved')),
        ];
        $totals['success_rate'] = $totals['runs'] > 0 ? round($totals['completed'] / $totals['runs'] * 100, 1) : 0.0;

        return [
            'window_days' => $days,
            'since' => $since->toIso8601String(),
            'prices' => ['input_per_mtok' => $inPrice, 'output_per_mtok' => $outPrice],
            'workflows' => array_values($byType),
            'chat' => $chat,
            'totals' => $totals,
            'recent_failures' => WorkflowRun::query()
                ->when($userId, fn ($q) => $q->where('user_id', $userId))
                ->where('status', 'failed')
                ->where('created_at', '>=', $since)
                ->latest('id')->limit(10)
                ->get(['id', 'workflow_type', 'error', 'created_at'])
                ->map(fn ($r) => [
                    'id' => $r->id, 'workflow' => $r->workflow_type,
                    'error' => mb_strimwidth((string) $r->error, 0, 160, '…'),
                    'at' => $r->created_at?->toIso8601String(),
                ])->all(),
        ];
    }

    /** @return array<string,mixed> */
    private function chat(Carbon $since, ?int $userId, float $inPrice, float $outPrice): array
    {
        $turns = AuditLog::query()
            ->where('category', 'maria_chat')
            ->where('created_at', '>=', $since)
            ->when($userId, fn ($q) => $q->where('owner_id', $userId))
            ->get(['metadata']);

        $in = $out = 0;
        foreach ($turns as $turn) {
            $in += (int) data_get($turn->metadata, 'input_tokens', 0);
            $out += (int) data_get($turn->metadata, 'output_tokens', 0);
        }

        return [
            'turns' => $turns->count(),
            'active_conversations' => AssistantConversation::query()
                ->when($userId, fn ($q) => $q->whereHas('profile', fn ($p) => $p->where('user_id', $userId)))
                ->where('last_message_at', '>=', $since)->count(),
            'input_tokens' => $in,
            'output_tokens' => $out,
            'cost' => round($this->cost($in, $out, $inPrice, $outPrice), 4),
        ];
    }

    private function cost(int $inputTokens, int $outputTokens, float $inPrice, float $outPrice): float
    {
        return $inputTokens / 1_000_000 * $inPrice + $outputTokens / 1_000_000 * $outPrice;
    }

    /** @return array{0: float, 1: float} */
    private function prices(): array
    {
        return [
            (float) Setting::get('claude_price_input_per_mtok', 1.0),
            (float) Setting::get('claude_price_output_per_mtok', 5.0),
        ];
    }
}
