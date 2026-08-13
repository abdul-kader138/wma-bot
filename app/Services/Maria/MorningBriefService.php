<?php

namespace App\Services\Maria;

use App\Models\Approval;
use App\Models\AssistantBrief;
use App\Models\AssistantProfile;
use App\Models\ConnectorAccount;
use App\Models\MariaProject;
use App\Models\MariaTask;
use App\Models\WorkflowRun;
use App\Services\Maria\Google\GmailReadClient;
use App\Services\Maria\Google\GoogleCalendarReadClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class MorningBriefService
{
    public function __construct(
        private readonly GmailReadClient $gmail,
        private readonly GoogleCalendarReadClient $calendar,
        private readonly StructuredWorkflowAgent $agent,
        private readonly PromptResolver $prompts,
    ) {}

    public function generate(AssistantProfile $profile): AssistantBrief
    {
        $profile->loadMissing('user');
        $user = $profile->user;
        $today = now($profile->timezone)->toDateString();
        $existing = AssistantBrief::where('user_id', $user->id)->where('type', 'morning')->whereDate('brief_date', $today)->first();
        if ($existing) {
            return $existing;
        }

        $run = WorkflowRun::create([
            'run_id' => (string) Str::uuid(), 'user_id' => $user->id,
            'workflow_type' => 'morning_brief', 'status' => 'running', 'started_at' => now(),
        ]);

        try {
            [$sources, $gaps] = $this->sources($profile);
            $prompt = $this->prompts->active();
            $result = $this->agent->run(
                $prompt['content'],
                "Prepare the Morning Command Brief for {$today}. Use only this JSON. Mark source gaps and never invent context:\n".json_encode($sources, JSON_THROW_ON_ERROR),
                'save_morning_brief', 'Return the final structured morning command brief.', $this->schema(),
            );

            return DB::transaction(function () use ($run, $user, $today, $result, $gaps, $sources, $prompt) {
                $run->update([
                    'status' => 'completed', 'input_references' => $this->references($sources),
                    'source_gaps' => $gaps, 'structured_output' => $result['data'],
                    'prompt_version' => $prompt['version'], 'input_tokens' => $result['usage']['input_tokens'],
                    'output_tokens' => $result['usage']['output_tokens'], 'finished_at' => now(),
                ]);

                return AssistantBrief::create([
                    'user_id' => $user->id, 'workflow_run_id' => $run->id,
                    'type' => 'morning', 'brief_date' => $today, 'content' => $result['data'],
                ]);
            });
        } catch (Throwable $error) {
            $run->update(['status' => 'failed', 'error' => mb_substr($error->getMessage(), 0, 5000), 'finished_at' => now()]);
            throw $error;
        }
    }

    private function sources(AssistantProfile $profile): array
    {
        $user = $profile->user;
        $sources = [
            'tasks' => MariaTask::where('user_id', $user->id)->whereNotIn('status', ['completed'])->orderBy('due_at')->limit(30)->get()->toArray(),
            'projects' => MariaProject::where('user_id', $user->id)->whereIn('stage', ['defined', 'active', 'waiting', 'review'])->limit(20)->get()->toArray(),
            'approvals' => Approval::where('user_id', $user->id)->where('decision', 'pending')->where('expires_at', '>', now())->limit(5)->get()->toArray(),
        ];
        $gaps = [];
        $connector = ConnectorAccount::where('user_id', $user->id)->where('provider', 'google')->where('status', 'active')->latest('id')->first();
        if (! $connector) {
            return [$sources, ['gmail_not_connected', 'calendar_not_connected']];
        }

        try {
            $messages = $this->gmail->listMessages($connector, 'is:unread -category:promotions -category:social', 10);
            $sources['emails'] = collect($messages['messages'] ?? [])->take(10)->map(fn ($item) => $this->gmail->getMessage($connector, $item['id']))->all();
        } catch (Throwable) {
            $gaps[] = 'gmail_unavailable';
        }
        try {
            $sources['calendar'] = $this->calendar->events($connector, now($profile->timezone), now($profile->timezone)->addHours(48), 30)['items'] ?? [];
        } catch (Throwable) {
            $gaps[] = 'calendar_unavailable';
        }

        return [$sources, $gaps];
    }

    private function references(array $sources): array
    {
        return collect($sources)->map(fn ($items) => collect($items)->pluck('id')->filter()->values()->all())->all();
    }

    private function schema(): array
    {
        $outcome = ['type' => 'object', 'properties' => [
            'title' => ['type' => 'string'], 'reason' => ['type' => 'string'],
            'owner' => ['type' => 'string'], 'next_action' => ['type' => 'string'], 'due_at' => ['type' => 'string'],
        ], 'required' => ['title', 'reason', 'owner', 'next_action']];

        return ['type' => 'object', 'properties' => [
            'date' => ['type' => 'string'], 'outcomes' => ['type' => 'array', 'maxItems' => 3, 'items' => $outcome],
            'meetings' => ['type' => 'array', 'items' => ['type' => 'object']],
            'approvals' => ['type' => 'array', 'maxItems' => 5, 'items' => ['type' => 'object']],
            'relationships' => ['type' => 'array', 'maxItems' => 5, 'items' => ['type' => 'object']],
            'risk' => ['type' => 'object'], 'maria_independent_work' => ['type' => 'array', 'items' => ['type' => 'string']],
            'source_gaps' => ['type' => 'array', 'items' => ['type' => 'string']], 'status' => ['type' => 'string'],
        ], 'required' => ['date', 'outcomes', 'meetings', 'approvals', 'risk', 'source_gaps', 'status']];
    }
}
