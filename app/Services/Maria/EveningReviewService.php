<?php

namespace App\Services\Maria;

use App\Models\Approval;
use App\Models\AssistantBrief;
use App\Models\AssistantProfile;
use App\Models\MariaTask;
use App\Models\WorkflowRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class EveningReviewService
{
    public function __construct(private readonly StructuredWorkflowAgent $agent, private readonly PromptResolver $prompts) {}

    public function generate(AssistantProfile $profile): AssistantBrief
    {
        $profile->loadMissing('user');
        $today = now($profile->timezone)->toDateString();
        $existing = AssistantBrief::where('user_id', $profile->user_id)->where('type', 'evening')->whereDate('brief_date', $today)->first();
        if ($existing) {
            return $existing;
        }

        $run = WorkflowRun::create([
            'run_id' => (string) Str::uuid(), 'user_id' => $profile->user_id,
            'workflow_type' => 'evening_review', 'status' => 'running', 'started_at' => now(),
        ]);

        try {
            $sources = [
                'completed_today' => MariaTask::where('user_id', $profile->user_id)->where('status', 'completed')->whereDate('updated_at', $today)->get()->toArray(),
                'open_tasks' => MariaTask::where('user_id', $profile->user_id)->whereNotIn('status', ['completed'])->orderBy('due_at')->limit(40)->get()->toArray(),
                'pending_approvals' => Approval::where('user_id', $profile->user_id)->where('decision', 'pending')->where('expires_at', '>', now())->limit(5)->get()->toArray(),
                'workflow_runs' => WorkflowRun::where('user_id', $profile->user_id)->whereDate('created_at', $today)->get()->toArray(),
            ];
            $prompt = $this->prompts->active();
            $result = $this->agent->run(
                $prompt['content'],
                "Prepare the Evening Review for {$today} using only this JSON. Explain unfinished work and give tomorrow's likely top three:\n".json_encode($sources, JSON_THROW_ON_ERROR),
                'save_evening_review', 'Return the final structured evening review.', $this->schema(),
            );

            return DB::transaction(function () use ($profile, $today, $run, $sources, $prompt, $result) {
                $run->update([
                    'status' => 'completed', 'input_references' => collect($sources)->map(fn ($items) => collect($items)->pluck('id')->all())->all(),
                    'structured_output' => $result['data'], 'prompt_version' => $prompt['version'],
                    'input_tokens' => $result['usage']['input_tokens'], 'output_tokens' => $result['usage']['output_tokens'], 'finished_at' => now(),
                ]);

                return AssistantBrief::create([
                    'user_id' => $profile->user_id, 'workflow_run_id' => $run->id,
                    'type' => 'evening', 'brief_date' => $today, 'content' => $result['data'],
                ]);
            });
        } catch (Throwable $error) {
            $run->update(['status' => 'failed', 'error' => mb_substr($error->getMessage(), 0, 5000), 'finished_at' => now()]);
            throw $error;
        }
    }

    private function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'date' => ['type' => 'string'], 'completed' => ['type' => 'array', 'items' => ['type' => 'string']],
            'awaiting_approval' => ['type' => 'array', 'maxItems' => 5, 'items' => ['type' => 'object']],
            'waiting_on_others' => ['type' => 'array', 'items' => ['type' => 'object']],
            'unfinished' => ['type' => 'array', 'items' => ['type' => 'object']],
            'tomorrow_top_three' => ['type' => 'array', 'maxItems' => 3, 'items' => ['type' => 'object']],
            'status' => ['type' => 'string'],
        ], 'required' => ['date', 'completed', 'awaiting_approval', 'waiting_on_others', 'unfinished', 'tomorrow_top_three', 'status']];
    }
}
