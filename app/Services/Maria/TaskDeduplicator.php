<?php

namespace App\Services\Maria;

use App\Models\MariaTask;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class TaskDeduplicator
{
    /** @return array{key:string,duplicate:?MariaTask} */
    public function inspect(User $owner, string $task, ?CarbonInterface $dueAt = null): array
    {
        $normalized = Str::of($task)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
        $date = $dueAt?->toDateString() ?? 'no-date';
        $key = hash('sha256', "{$normalized}|{$date}");

        $duplicate = MariaTask::where('user_id', $owner->id)
            ->where('deduplication_key', $key)
            ->whereNotIn('status', ['completed'])
            ->oldest('id')->first();

        return ['key' => $key, 'duplicate' => $duplicate];
    }
}
