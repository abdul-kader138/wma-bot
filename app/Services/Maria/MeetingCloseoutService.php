<?php

namespace App\Services\Maria;

use App\Models\Meeting;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class MeetingCloseoutService
{
    public function __construct(
        private readonly StructuredWorkflowAgent $agent,
        private readonly PromptResolver $prompts,
        private readonly CommitmentTaskService $tasks,
        private readonly AuditLogger $audit,
    ) {}

    public function close(Meeting $meeting, string $notes): Meeting
    {
        $meeting->loadMissing('user');
        $prompt = $this->prompts->active();
        $result = $this->agent->run(
            $prompt['content'],
            'Close out this meeting using only the supplied notes. Extract explicit decisions and commitments. Draft but do not send a thank-you. Meeting: '.json_encode($meeting->only(['title', 'starts_at', 'attendees', 'objective']), JSON_THROW_ON_ERROR)."\nNotes:\n{$notes}",
            'save_meeting_closeout', 'Return meeting decisions, actions, unanswered questions, and an unsent thank-you draft.', $this->schema(),
        );

        return DB::transaction(function () use ($meeting, $notes, $result) {
            $data = $result['data'];
            $meeting->update([
                'notes_source' => $notes,
                'decisions' => $data['decisions'] ?? [],
                'action_items' => $data['action_items'] ?? [],
                'thank_you_draft' => $data['thank_you_draft'] ?? null,
                'follow_up_at' => $data['follow_up_at'] ?? null,
                'preparation_status' => 'closed_out',
            ]);

            foreach ($data['action_items'] ?? [] as $action) {
                $this->tasks->create($meeting->user, $action, 'meeting', "meeting:{$meeting->id}");
            }

            $this->audit->recordContext(
                category: 'maria_meeting', action: 'closeout', actorId: $meeting->user_id,
                ownerId: $meeting->user_id, subjectPath: "meeting:{$meeting->id}",
                metadata: ['task_count' => count($data['action_items'] ?? []), 'prompt_version' => $result['usage']],
            );

            return $meeting->refresh();
        });
    }

    private function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'decisions' => ['type' => 'array', 'items' => ['type' => 'string']],
            'action_items' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                'task' => ['type' => 'string'], 'owner_name' => ['type' => 'string'],
                'due_at' => ['type' => 'string'], 'reason' => ['type' => 'string'],
            ], 'required' => ['task', 'owner_name']]],
            'unanswered_questions' => ['type' => 'array', 'items' => ['type' => 'string']],
            'thank_you_draft' => ['type' => 'string'], 'follow_up_at' => ['type' => 'string'],
        ], 'required' => ['decisions', 'action_items', 'unanswered_questions', 'thank_you_draft']];
    }
}
