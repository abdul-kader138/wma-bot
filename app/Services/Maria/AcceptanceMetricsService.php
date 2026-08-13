<?php

namespace App\Services\Maria;

use App\Models\AssistantAction;
use App\Models\AssistantBrief;
use App\Models\MariaProject;
use App\Models\MariaQualityEvent;
use App\Models\Meeting;
use App\Models\RelationshipRecommendation;
use App\Models\User;
use App\Models\WorkflowRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AcceptanceMetricsService
{
    public function calculate(User $owner, int $days = 30): array
    {
        $from = now()->subDays($days);
        $runs = WorkflowRun::where('user_id', $owner->id)->where('created_at', '>=', $from)->get();
        $events = MariaQualityEvent::where('user_id', $owner->id)->where('occurred_at', '>=', $from)->get();
        $relationships = RelationshipRecommendation::where('user_id', $owner->id)->where('recommendation_date', '>=', $from->toDateString())->get();
        $decidedRelationships = $relationships->whereIn('status', ['accepted', 'rejected']);
        $meetings = Meeting::where('user_id', $owner->id)->whereIn('tier', ['A', 'B'])->whereBetween('starts_at', [$from, now()])->get();
        $projects = MariaProject::where('user_id', $owner->id)->whereIn('stage', ['defined', 'active', 'waiting', 'review'])->get();
        $contentRuns = $runs->whereIn('workflow_type', ['content_package', 'acm_weekly_production']);
        $voiceCorrections = $events->where('event_type', 'correction')->where('category', 'voice')->count();

        return [
            'period' => ['days' => $days, 'from' => $from->toDateString(), 'to' => now()->toDateString()],
            'metrics' => [
                $this->zeroMetric('Unauthorized external actions', $events->where('event_type', 'safety_incident')->where('category', 'unauthorized_action')->count()),
                $this->zeroMetric('Duplicate external actions', $events->where('event_type', 'safety_incident')->where('category', 'duplicate_action')->count()),
                $this->zeroMetric('Recipient or attachment errors', $events->where('event_type', 'safety_incident')->whereIn('category', ['recipient', 'attachment'])->count()),
                $this->unmeasured('Critical tracked deadline recall', 'Requires a manually verified inventory of all critical deadlines.'),
                $this->rateMetric('Correct domain routing', max(0, $runs->count() - $events->where('event_type', 'correction')->where('category', 'classification')->count()), $runs->count(), 95),
                $this->unmeasured('Useful top-three priorities', 'Priority accept/reject feedback is not yet captured per brief outcome.'),
                $this->rateMetric('Voice accepted without major edit', max(0, $contentRuns->count() - $voiceCorrections), $contentRuns->count(), 85),
                $this->rateMetric('Relationship recommendation acceptance', $decidedRelationships->where('status', 'accepted')->count(), $decidedRelationships->count(), 80),
                $this->rateMetric('Tier A/B meeting brief completion', $meetings->whereIn('preparation_status', ['prepared', 'closed_out'])->count(), $meetings->count(), 95),
                $this->unmeasured('Follow-up drafted within two hours', 'Meeting notes availability time is not recorded separately.'),
                $this->rateMetric('Active projects with owner/action/date', $projects->filter(fn ($project) => filled($project->owner_name) && filled($project->next_action) && $project->next_action_at)->count(), $projects->count(), 100),
            ],
            'operations' => [
                'workflow_runs' => $runs->count(), 'completed_workflows' => $runs->where('status', 'completed')->count(),
                'failed_workflows' => $runs->where('status', 'failed')->count(),
                'estimated_minutes_saved' => $runs->sum('estimated_manual_minutes'),
                'verified_minutes_saved' => $runs->whereNotNull('time_saving_verified_at')->sum('verified_time_saved_minutes'),
                'verified_runs' => $runs->whereNotNull('time_saving_verified_at')->count(),
                'estimated_cost' => round((float) $runs->sum('estimated_cost'), 6),
                'completed_external_actions' => AssistantAction::where('user_id', $owner->id)->where('status', 'completed')->where('executed_at', '>=', $from)->count(),
                'corrections' => $events->where('event_type', 'correction')->count(), 'safety_incidents' => $events->where('event_type', 'safety_incident')->count(),
            ],
        ];
    }

    public function snapshot(User $owner, int $days = 30): AssistantBrief
    {
        $date = now($owner->assistantProfile?->timezone ?? config('app.timezone'))->toDateString();
        $existing = AssistantBrief::where('user_id', $owner->id)->where('type', 'acceptance_30_day')->whereDate('brief_date', $date)->first();
        if ($existing) {
            return $existing;
        }
        $content = $this->calculate($owner, $days);

        return DB::transaction(function () use ($owner, $date, $content) {
            $run = WorkflowRun::create(['run_id' => (string) Str::uuid(), 'user_id' => $owner->id, 'workflow_type' => 'acceptance_30_day_snapshot', 'status' => 'completed', 'structured_output' => $content, 'started_at' => now(), 'finished_at' => now()]);

            return AssistantBrief::create(['user_id' => $owner->id, 'workflow_run_id' => $run->id, 'type' => 'acceptance_30_day', 'brief_date' => $date, 'content' => $content]);
        });
    }

    private function zeroMetric(string $name, int $incidents): array
    {
        return ['name' => $name, 'value' => $incidents, 'unit' => 'incidents', 'target' => 0, 'status' => $incidents === 0 ? 'pass' : 'fail', 'numerator' => null, 'denominator' => null];
    }

    private function rateMetric(string $name, int $numerator, int $denominator, int $target): array
    {
        if ($denominator === 0) {
            return $this->unmeasured($name, 'No eligible records in this period.', $target);
        }
        $value = round($numerator / $denominator * 100, 1);

        return ['name' => $name, 'value' => $value, 'unit' => 'percent', 'target' => $target, 'status' => $value >= $target ? 'pass' : 'fail', 'numerator' => $numerator, 'denominator' => $denominator];
    }

    private function unmeasured(string $name, string $reason, ?int $target = null): array
    {
        return ['name' => $name, 'value' => null, 'unit' => null, 'target' => $target, 'status' => 'not_measured', 'reason' => $reason, 'numerator' => null, 'denominator' => null];
    }
}
