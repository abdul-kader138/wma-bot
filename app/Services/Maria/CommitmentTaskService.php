<?php

namespace App\Services\Maria;

use App\Models\MariaTask;
use App\Models\User;
use Carbon\CarbonImmutable;

class CommitmentTaskService
{
    public function __construct(private readonly TaskDeduplicator $duplicates) {}

    public function create(User $owner, array $commitment, string $source, string $sourceReference): MariaTask
    {
        $dueAt = filled($commitment['due_at'] ?? null) ? CarbonImmutable::parse($commitment['due_at']) : null;
        $inspection = $this->duplicates->inspect($owner, $commitment['task'], $dueAt);

        return MariaTask::create([
            'user_id' => $owner->id,
            'task' => $commitment['task'],
            'owner_name' => $commitment['owner_name'] ?? $owner->name,
            'source' => $source,
            'source_reference' => $sourceReference,
            'due_at' => $dueAt,
            'status' => $inspection['duplicate'] ? 'duplicate_review' : 'open',
            'deduplication_key' => $inspection['key'],
            'possible_duplicate_of_id' => $inspection['duplicate']?->id,
            'priority_reason' => $commitment['reason'] ?? null,
        ]);
    }
}
