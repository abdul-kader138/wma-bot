<?php

namespace App\Services\Maria;

use App\Models\AcmProductionPlan;
use App\Models\WorkflowRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AcmProductionService
{
    public function __construct(private readonly StructuredWorkflowAgent $agent, private readonly PromptResolver $prompts, private readonly ClaimsVerificationService $claims) {}

    public function generate(AcmProductionPlan $plan): AcmProductionPlan
    {
        if ($plan->status === 'draft' && $plan->production_package) {
            return $plan;
        }
        $verification = $this->claims->verify($plan->user, $plan->core_claims ?? [], 'All Catholic Media');
        if (! $verification['allowed']) {
            $plan->update(['status' => 'blocked_claims', 'claim_verification' => $verification]);
            throw ValidationException::withMessages(['core_claims' => 'Claims are not verified and permitted for All Catholic Media: '.implode('; ', $verification['blocked_claims'])]);
        }
        $run = WorkflowRun::create(['run_id' => (string) Str::uuid(), 'user_id' => $plan->user_id, 'workflow_type' => 'acm_weekly_production', 'status' => 'running', 'started_at' => now()]);
        try {
            $prompt = $this->prompts->active('acm_weekly_production');
            $input = ['week_start' => $plan->week_start->toDateString(), 'theme' => $plan->theme, 'source_notes' => $plan->source_notes, 'verified_claims' => $verification['matches'], 'owner' => $plan->owner_name, 'approval_deadline' => $plan->approval_deadline->toIso8601String(), 'rules' => ['draft_only', 'no_publication', 'attribute_sources', 'brand_all_catholic_media_only']];
            $result = $this->agent->run($prompt['content'], 'Prepare the weekly All Catholic Media production plan from this JSON only: '.json_encode($input, JSON_THROW_ON_ERROR), 'save_acm_production_plan', 'Return the draft weekly production package.', $this->schema());

            return DB::transaction(function () use ($plan, $run, $prompt, $result, $verification) {
                $plan->update(['workflow_run_id' => $run->id, 'production_package' => $result['data'], 'claim_verification' => $verification, 'status' => 'draft', 'generated_at' => now()]);
                $run->update(['status' => 'completed', 'input_references' => ['production_plan_id' => $plan->id, 'claim_ids' => collect($verification['matches'])->pluck('claim_id')->all()], 'structured_output' => ['production_plan_id' => $plan->id], 'prompt_version' => $prompt['version'], 'input_tokens' => $result['usage']['input_tokens'], 'output_tokens' => $result['usage']['output_tokens'], 'estimated_manual_minutes' => 150, 'finished_at' => now()]);

                return $plan->refresh();
            });
        } catch (Throwable $error) {
            $run->update(['status' => 'failed', 'error' => mb_substr($error->getMessage(), 0, 5000), 'finished_at' => now()]);
            throw $error;
        }
    }

    private function schema(): array
    {
        $string = ['type' => 'string'];

        return ['type' => 'object', 'properties' => [
            'theme' => $string, 'podcast_or_reflection_plan' => ['type' => 'array', 'items' => $string],
            'newsletter_sections' => ['type' => 'array', 'items' => $string], 'social_package' => ['type' => 'array', 'items' => ['type' => 'object']],
            'assets' => ['type' => 'array', 'items' => ['type' => 'object']], 'owners' => ['type' => 'array', 'items' => ['type' => 'object']],
            'approval_deadline' => $string, 'proposed_publication_schedule' => ['type' => 'array', 'items' => ['type' => 'object']],
            'attributions' => ['type' => 'array', 'items' => $string],
        ], 'required' => ['theme', 'podcast_or_reflection_plan', 'newsletter_sections', 'social_package', 'assets', 'owners', 'approval_deadline', 'proposed_publication_schedule', 'attributions']];
    }
}
