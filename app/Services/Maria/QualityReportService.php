<?php

namespace App\Services\Maria;

use App\Models\AssistantBrief;
use App\Models\AssistantProfile;
use App\Models\MariaQualityEvent;
use App\Models\WorkflowRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QualityReportService
{
    public function generate(AssistantProfile $profile, ?string $weekStart = null): AssistantBrief
    {
        $start = $weekStart ? Carbon::parse($weekStart, $profile->timezone)->startOfWeek() : now($profile->timezone)->startOfWeek();
        $existing = AssistantBrief::where('user_id', $profile->user_id)->where('type', 'quality_report')->whereDate('brief_date', $start)->first();
        if ($existing) {
            return $existing;
        }
        $end = $start->copy()->endOfWeek();
        $runs = WorkflowRun::where('user_id', $profile->user_id)->whereBetween('created_at', [$start->copy()->utc(), $end->copy()->utc()])->get();
        $events = MariaQualityEvent::where('user_id', $profile->user_id)->whereBetween('occurred_at', [$start->copy()->utc(), $end->copy()->utc()])->get();
        $corrections = $events->where('event_type', 'correction')->groupBy('category')->map(fn ($items, $category) => ['category' => $category, 'count' => $items->count(), 'latest' => $items->sortByDesc('occurred_at')->first()->description])->sortByDesc('count')->take(3)->values()->all();
        $failures = $runs->where('status', 'failed')->groupBy('workflow_type')->sortByDesc->count();
        $improvement = $corrections[0] ?? ($failures->isNotEmpty() ? ['category' => 'workflow_failure', 'count' => $failures->first()->count(), 'latest' => 'Investigate '.$failures->keys()->first().' failures.'] : ['category' => 'none', 'count' => 0, 'latest' => 'Continue monitoring and verify time savings.']);
        $content = [
            'week_start' => $start->toDateString(), 'week_end' => $end->toDateString(),
            'workflow_metrics' => ['total' => $runs->count(), 'completed' => $runs->where('status', 'completed')->count(), 'failed' => $runs->where('status', 'failed')->count()],
            'largest_estimated_time_savings' => $runs->sortByDesc('estimated_manual_minutes')->take(3)->map(fn ($run) => ['workflow_run_id' => $run->id, 'workflow_type' => $run->workflow_type, 'estimated_minutes' => $run->estimated_manual_minutes, 'label' => 'estimated'])->values()->all(),
            'largest_verified_time_savings' => $runs->whereNotNull('time_saving_verified_at')->sortByDesc('verified_time_saved_minutes')->take(3)->map(fn ($run) => ['workflow_run_id' => $run->id, 'workflow_type' => $run->workflow_type, 'verified_minutes' => $run->verified_time_saved_minutes, 'verified_at' => $run->time_saving_verified_at->toIso8601String(), 'label' => 'verified'])->values()->all(),
            'recurring_corrections' => $corrections,
            'safety_incidents' => $events->where('event_type', 'safety_incident')->map(fn ($event) => ['id' => $event->id, 'severity' => $event->severity, 'category' => $event->category, 'description' => $event->description, 'status' => $event->status])->values()->all(),
            'recommended_improvement' => ['category' => $improvement['category'], 'reason' => $improvement['latest'], 'occurrences' => $improvement['count']],
        ];
        $run = WorkflowRun::create(['run_id' => (string) Str::uuid(), 'user_id' => $profile->user_id, 'workflow_type' => 'quality_report', 'status' => 'running', 'started_at' => now()]);

        return DB::transaction(function () use ($profile, $start, $run, $content, $runs, $events) {
            $run->update(['status' => 'completed', 'input_references' => ['workflow_run_ids' => $runs->pluck('id')->all(), 'quality_event_ids' => $events->pluck('id')->all()], 'structured_output' => $content, 'estimated_manual_minutes' => 20, 'finished_at' => now()]);

            return AssistantBrief::create(['user_id' => $profile->user_id, 'workflow_run_id' => $run->id, 'type' => 'quality_report', 'brief_date' => $start->toDateString(), 'content' => $content]);
        });
    }
}
