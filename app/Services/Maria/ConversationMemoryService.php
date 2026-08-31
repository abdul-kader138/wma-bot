<?php

namespace App\Services\Maria;

use App\Models\AssistantConversation;
use App\Models\AssistantMemory;
use App\Models\User;

/**
 * Keeps a chat usable over many turns without an unbounded prompt:
 *  - long histories are compressed into a rolling summary on the conversation;
 *  - durable owner facts live in assistant_memories, independent of any one chat.
 * Both are surfaced to the agent as a short preamble on the current message.
 */
class ConversationMemoryService
{
    /** Turns (user+assistant messages) to keep verbatim; older ones fold into the summary. */
    private const KEEP_MESSAGES = 16;

    /** Only bother summarising once history is meaningfully longer than what we keep. */
    private const SUMMARISE_AT = 24;

    public function __construct(private readonly StructuredWorkflowAgent $agent) {}

    /** Context block prepended to the owner's message so the agent has continuity. */
    public function preamble(User $owner, AssistantConversation $conversation): string
    {
        $parts = [];

        $facts = AssistantMemory::where('user_id', $owner->id)->orderBy('key')->get();
        if ($facts->isNotEmpty()) {
            $parts[] = "Durable facts about the owner (already known — do not re-ask):\n"
                .$facts->map(fn ($f) => "- {$f->key}: {$f->value}")->implode("\n");
        }

        if (filled($conversation->summary)) {
            $parts[] = "Summary of earlier turns in this conversation:\n".$conversation->summary;
        }

        if ($parts === []) {
            return '';
        }

        return "(Context for you only; do not mention this framing to the owner.)\n".implode("\n\n", $parts)."\n\n---\n\n";
    }

    /**
     * If the history is long, replace its older half with an updated summary and
     * return the trimmed history. Safe to call every turn; it no-ops when short.
     *
     * @param  array<int,array<string,mixed>>  $history
     * @return array{0: array<int,array<string,mixed>>, 1: ?string} [trimmedHistory, newSummary|null]
     */
    public function compress(AssistantConversation $conversation, array $history): array
    {
        if (count($history) <= self::SUMMARISE_AT) {
            return [$history, null];
        }

        $keep = array_slice($history, -self::KEEP_MESSAGES);
        $fold = array_slice($history, 0, count($history) - self::KEEP_MESSAGES);

        $transcript = collect($fold)
            ->map(fn ($m) => strtoupper((string) ($m['role'] ?? '?')).': '.(is_string($m['content'] ?? null) ? $m['content'] : json_encode($m['content'])))
            ->implode("\n");

        try {
            $result = $this->agent->run(
                'You compress a chat transcript into durable notes. Keep only what a chief of staff would need later: '
                .'decisions, open threads, commitments, preferences, names. Drop small talk. Third person, terse.',
                ($conversation->summary ? "Existing summary:\n{$conversation->summary}\n\n" : '')
                    ."Fold these earlier turns into an updated summary:\n{$transcript}",
                'save_summary', 'Return the updated rolling summary.',
                ['type' => 'object', 'properties' => ['summary' => ['type' => 'string']], 'required' => ['summary']],
            );
            $summary = trim((string) ($result['data']['summary'] ?? ''));
        } catch (\Throwable) {
            // If summarisation fails, keep the full history rather than lose context.
            return [$history, null];
        }

        return [$keep, $summary !== '' ? $summary : $conversation->summary];
    }
}
