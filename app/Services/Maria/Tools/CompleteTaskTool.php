<?php

namespace App\Services\Maria\Tools;

use App\Models\MariaTask;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class CompleteTaskTool extends BaseAssistantTool
{
    public function name(): string
    {
        return 'complete_task';
    }

    public function description(): string
    {
        return 'Mark one of the owner’s existing Maria tasks as completed. This only changes an internal record and never contacts anyone.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => [
            'task_id' => ['type' => 'integer', 'description' => 'The numeric id of the task to close, as shown by list_tasks.'],
            'note' => ['type' => 'string', 'description' => 'Optional short note recorded as the reason the task is done.'],
        ], 'required' => ['task_id']];
    }

    public function execute(User $owner, array $input): array
    {
        $data = Validator::make($input, [
            'task_id' => ['required', 'integer'],
            'note' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        $task = MariaTask::query()
            ->where('user_id', $owner->id)
            ->where('id', $data['task_id'])
            ->first();

        if (! $task) {
            return ['error' => "No task with id {$data['task_id']} belongs to this owner. Ask the user to confirm the task from list_tasks."];
        }

        if ($task->status === 'completed') {
            return ['id' => $task->id, 'task' => $task->task, 'status' => 'completed', 'already_completed' => true];
        }

        $task->update([
            'status' => 'completed',
            'priority_reason' => filled($data['note'] ?? null) ? $data['note'] : $task->priority_reason,
        ]);

        return ['id' => $task->id, 'task' => $task->task, 'status' => 'completed', 'already_completed' => false];
    }
}
