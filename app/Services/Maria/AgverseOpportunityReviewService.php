<?php

namespace App\Services\Maria;

use App\Models\AgverseOpportunity;
use App\Models\AssistantBrief;
use App\Models\AssistantProfile;
use App\Models\WorkflowRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AgverseOpportunityReviewService
{
    public function review(AssistantProfile $profile, ?string $reviewDate = null): AssistantBrief
    {
        $date = $reviewDate ? Carbon::parse($reviewDate, $profile->timezone) : now($profile->timezone);
        $date = $date->startOfDay();
        $existing = AssistantBrief::where('user_id', $profile->user_id)->where('type', 'agverse_opportunities')->whereDate('brief_date', $date)->first();
        if ($existing) {
            return $existing;
        }

        $run = WorkflowRun::create([
            'run_id' => (string) Str::uuid(), 'user_id' => $profile->user_id,
            'workflow_type' => 'agverse_opportunity_review', 'status' => 'running', 'started_at' => now(),
        ]);

        $opportunities = AgverseOpportunity::where('user_id', $profile->user_id)->where('status', 'active')->get();
        foreach ($opportunities as $opportunity) {
            $opportunity->update(['priority_score' => $this->score($opportunity)]);
        }
        $ranked = $opportunities->sortByDesc('priority_score')->values();
        $content = [
            'review_date' => $date->toDateString(),
            'opportunities' => $ranked->map(fn (AgverseOpportunity $item, int $index) => [
                'rank' => $index + 1, 'id' => $item->id, 'name' => $item->name,
                'organization' => $item->organization, 'expected_value' => $item->expected_value,
                'currency' => $item->currency, 'priority_score' => $item->priority_score,
                'scores' => ['value' => $item->value_score, 'strategic_fit' => $item->strategic_fit_score, 'urgency' => $item->urgency_score, 'evidence' => $item->evidence_score, 'effort' => $item->effort_score, 'risk' => $item->risk_score],
                'verified_facts' => $item->verified_facts ?? [], 'hypotheses' => $item->hypotheses ?? [],
                'evidence_links' => $item->evidence_links ?? [], 'next_step' => $item->next_step,
                'next_step_owner' => $item->next_step_owner, 'next_step_at' => $item->next_step_at?->toIso8601String(),
                'approval_required' => $item->approval_required || $item->risk_score >= 4,
            ])->all(),
            'top_three_next_steps' => $ranked->take(3)->map(fn (AgverseOpportunity $item) => [
                'opportunity_id' => $item->id, 'name' => $item->name, 'next_step' => $item->next_step,
                'owner' => $item->next_step_owner, 'date' => $item->next_step_at?->toDateString(),
                'approval_required' => $item->approval_required || $item->risk_score >= 4,
            ])->all(),
        ];

        return DB::transaction(function () use ($profile, $date, $run, $ranked, $content) {
            $run->update([
                'status' => 'completed', 'input_references' => ['opportunity_ids' => $ranked->pluck('id')->all()],
                'structured_output' => $content, 'estimated_manual_minutes' => 30, 'finished_at' => now(),
            ]);

            return AssistantBrief::create([
                'user_id' => $profile->user_id, 'workflow_run_id' => $run->id,
                'type' => 'agverse_opportunities', 'brief_date' => $date->toDateString(), 'content' => $content,
            ]);
        });
    }

    public function score(AgverseOpportunity $opportunity): int
    {
        foreach (['value_score', 'strategic_fit_score', 'urgency_score', 'evidence_score', 'effort_score', 'risk_score'] as $field) {
            if ($opportunity->{$field} < 1 || $opportunity->{$field} > 5) {
                throw ValidationException::withMessages([$field => 'Scores must be integers from 1 to 5.']);
            }
        }

        return $opportunity->value_score + $opportunity->strategic_fit_score + $opportunity->urgency_score
            + $opportunity->evidence_score - $opportunity->effort_score - $opportunity->risk_score;
    }
}
