<?php

namespace App\Services\Maria\Tools;

use App\Models\MariaTask;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class CreateTaskTool extends BaseAssistantTool
{
    public function name(): string
    {
        return 'create_task';
    }

    public function description(): string
    {
        return 'Create an internal task for the owner. This does not contact anyone externally.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => [
            'task' => ['type' => 'string'], 'owner_name' => ['type' => 'string'],
            'due_at' => ['type' => 'string', 'description' => 'ISO 8601 date/time with timezone'],
            'status' => ['type' => 'string', 'enum' => ['open', 'scheduled', 'waiting', 'blocked']],
            'priority_reason' => ['type' => 'string'],
        ], 'required' => ['task', 'owner_name']];
    }

    public function execute(User $owner, array $input): array
    {
        $data = Validator::make($input, [
            'task' => ['required', 'string', 'max:5000'],
            'owner_name' => ['required', 'string', 'max:255'],
            'due_at' => ['nullable', 'date'],
            'status' => ['nullable', 'in:open,scheduled,waiting,blocked'],
            'priority_reason' => ['nullable', 'string', 'max:5000'],
        ])->validate();

        $task = MariaTask::create([
            ...$data, 'user_id' => $owner->id, 'source' => 'automation',
            'status' => $data['status'] ?? 'open',
        ]);

        return ['id' => $task->id, 'task' => $task->task, 'status' => $task->status, 'due_at' => $task->due_at?->toIso8601String()];
    }
}
