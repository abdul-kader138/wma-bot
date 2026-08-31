<?php

namespace App\Services\Maria\Tools;

use App\Models\MariaProject;
use App\Models\MariaTask;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

class UpdateTaskTool extends BaseAssistantTool
{
    public function name(): string
    {
        return 'update_task';
    }

    public function description(): string
    {
        return 'Change an existing task the owner owns: its due date, its status (open, scheduled, waiting, blocked), its follow-up date, or which project it belongs to. Internal record change only — to close a task use complete_task instead.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => [
            'task_id' => ['type' => 'integer', 'description' => 'Numeric id from list_tasks.'],
            'due_at' => ['type' => 'string', 'description' => 'New due date/time, ISO 8601. Send an empty string to clear it.'],
            'status' => ['type' => 'string', 'enum' => ['open', 'scheduled', 'waiting', 'blocked']],
            'follow_up_at' => ['type' => 'string', 'description' => 'New follow-up date/time, ISO 8601. Empty string clears it.'],
            'project_name' => ['type' => 'string', 'description' => 'Exact name of one of the owner\'s projects to link this task to.'],
        ], 'required' => ['task_id']];
    }

    public function execute(User $owner, array $input): array
    {
        $data = Validator::make($input, [
            'task_id' => ['required', 'integer'],
            'due_at' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'in:open,scheduled,waiting,blocked'],
            'follow_up_at' => ['sometimes', 'nullable', 'string'],
            'project_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ])->validate();

        $task = MariaTask::query()->where('user_id', $owner->id)->where('id', $data['task_id'])->first();

        if (! $task) {
            return ['error' => "No task with id {$data['task_id']} belongs to this owner."];
        }

        $changes = [];

        if (array_key_exists('due_at', $data)) {
            $changes['due_at'] = filled($data['due_at']) ? CarbonImmutable::parse($data['due_at']) : null;
        }

        if (array_key_exists('follow_up_at', $data)) {
            $changes['follow_up_at'] = filled($data['follow_up_at']) ? CarbonImmutable::parse($data['follow_up_at']) : null;
        }

        if (isset($data['status'])) {
            $changes['status'] = $data['status'];
        }

        if (filled($data['project_name'] ?? null)) {
            $project = MariaProject::query()->where('user_id', $owner->id)->where('name', $data['project_name'])->first();
            if (! $project) {
                return ['error' => "No project named \"{$data['project_name']}\" belongs to this owner. Use list_projects for exact names."];
            }
            $changes['project_id'] = $project->id;
        }

        if ($changes === []) {
            return ['error' => 'Nothing to change. Provide at least one of due_at, status, follow_up_at, or project_name.'];
        }

        $task->update($changes);

        return [
            'id' => $task->id,
            'task' => $task->task,
            'status' => $task->status,
            'due_at' => $task->due_at?->toIso8601String(),
            'follow_up_at' => $task->follow_up_at?->toIso8601String(),
            'project_id' => $task->project_id,
        ];
    }
}
