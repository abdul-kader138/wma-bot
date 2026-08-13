<?php

namespace App\Services\Maria;

use App\Models\AssistantAlert;
use App\Models\AssistantProfile;
use App\Models\MariaProject;
use App\Models\MariaTask;

class DeadlineMonitorService
{
    public function run(AssistantProfile $profile): array
    {
        $now = now($profile->timezone);
        $candidates = collect();
        MariaTask::where('user_id', $profile->user_id)->whereNotIn('status', ['completed', 'duplicate_review'])
            ->where(fn ($query) => $query->where('due_at', '<=', $now->copy()->addDay())->orWhere('status', 'waiting'))
            ->get()->each(fn ($task) => $candidates->push($this->taskCandidate($task, $now)));
        MariaProject::where('user_id', $profile->user_id)->whereNotIn('stage', ['complete', 'archived', 'paused'])
            ->whereNotNull('deadline_at')->where('deadline_at', '<=', $now->copy()->addDays(3))->get()
            ->each(fn ($project) => $candidates->push($this->projectCandidate($project, $now)));

        $activeKeys = [];
        $alerts = $candidates->filter()->map(function (array $candidate) use ($profile, &$activeKeys) {
            $hash = hash('sha256', json_encode($candidate['state'], JSON_THROW_ON_ERROR));
            $activeKeys[] = [$candidate['type'], $candidate['subject_type'], $candidate['subject_id'], $hash];
            $alert = AssistantAlert::firstOrCreate(
                [
                    'user_id' => $profile->user_id, 'type' => $candidate['type'],
                    'subject_type' => $candidate['subject_type'], 'subject_id' => $candidate['subject_id'],
                    'state_hash' => $hash,
                ],
                [
                    'severity' => $candidate['severity'], 'status' => 'active',
                    'message' => $candidate['message'], 'first_seen_at' => now(), 'last_seen_at' => now(),
                ],
            );
            $alert->update([
                'severity' => $candidate['severity'], 'status' => 'active',
                'message' => $candidate['message'], 'last_seen_at' => now(),
            ]);

            return $alert->refresh();
        })->values();

        // Resolve alerts only when their exact underlying subject is no longer
        // actionable. Unchanged states reuse the same record and create no alert flood.
        AssistantAlert::where('user_id', $profile->user_id)->where('status', 'active')->get()
            ->reject(fn ($alert) => collect($activeKeys)->contains(fn ($key) => $key === [$alert->type, $alert->subject_type, $alert->subject_id, $alert->state_hash]))
            ->each->update(['status' => 'resolved']);

        return $alerts->all();
    }

    private function taskCandidate(MariaTask $task, $now): array
    {
        $overdue = $task->due_at?->lt($now) ?? false;

        return [
            'type' => $task->status === 'waiting' ? 'waiting_followup' : 'task_deadline',
            'severity' => $overdue ? 'high' : 'normal', 'subject_type' => MariaTask::class, 'subject_id' => $task->id,
            'message' => $overdue ? "Overdue: {$task->task}" : ($task->status === 'waiting' ? "Waiting item needs review: {$task->task}" : "Due soon: {$task->task}"),
            'state' => [$task->status, $task->due_at?->toIso8601String(), $task->follow_up_at?->toIso8601String()],
        ];
    }

    private function projectCandidate(MariaProject $project, $now): array
    {
        return [
            'type' => 'project_deadline', 'severity' => $project->deadline_at->lt($now) ? 'high' : 'normal',
            'subject_type' => MariaProject::class, 'subject_id' => $project->id,
            'message' => ($project->deadline_at->lt($now) ? 'Overdue project: ' : 'Project deadline approaching: ').$project->name,
            'state' => [$project->stage, $project->status, $project->deadline_at->toIso8601String()],
        ];
    }
}
