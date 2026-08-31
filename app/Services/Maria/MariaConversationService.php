<?php

namespace App\Services\Maria;

use App\Models\AssistantChannelIdentity;
use App\Models\AssistantConversation;
use App\Services\AuditLogger;
use App\Services\Maria\Support\Language;
use Illuminate\Support\Facades\DB;

class MariaConversationService
{
    public function __construct(
        private readonly MariaAgent $agent,
        private readonly AuditLogger $audit,
        private readonly ConversationMemoryService $memory,
    ) {}

    public function handle(AssistantChannelIdentity $identity, string $message): string
    {
        $identity->loadMissing('profile.user');
        $profile = $identity->profile;
        $owner = $profile->user;

        $conversation = AssistantConversation::firstOrCreate(
            [
                'assistant_profile_id' => $profile->id,
                'assistant_channel_identity_id' => $identity->id,
                'status' => 'active',
            ],
            ['history' => []],
        );

        // Fold an over-long history into the rolling summary before this turn.
        [$history, $newSummary] = $this->memory->compress($conversation, $conversation->history ?? []);

        $preamble = Language::chatDirective($profile->language)
            .$this->memory->preamble($owner, $conversation);

        try {
            $result = $this->agent->handle($owner, $preamble.$message, $history);
        } catch (ClaudeRateLimited) {
            // Transient shared-budget pressure. Do not persist a broken turn or
            // burn tokens; ask the owner to retry in a moment.
            return 'Maria is briefly at her request limit and could not process that. Please send it again in a minute.';
        }

        // Store the owner's real message, not the preamble-wrapped one.
        $history[] = ['role' => 'user', 'content' => $message];
        $history[] = ['role' => 'assistant', 'content' => $result['text']];

        DB::transaction(function () use ($conversation, $history, $newSummary, $result) {
            $conversation->update([
                'history' => array_slice($history, -40),
                'summary' => $newSummary ?? $conversation->summary,
                'last_prompt_version' => $result['prompt_version'],
                'input_tokens' => $conversation->input_tokens + (int) $result['usage']['input_tokens'],
                'output_tokens' => $conversation->output_tokens + (int) $result['usage']['output_tokens'],
                'last_message_at' => now(),
            ]);
        });

        // Governance trail for the chat channel, mirroring how approvals and
        // external actions are audited. Message text is deliberately not stored —
        // only which tools ran, the prompt version, and token cost.
        $this->audit->recordContext(
            category: 'maria_chat',
            action: 'message',
            actorId: $profile->user_id,
            ownerId: $profile->user_id,
            subjectPath: "assistant_conversation:{$conversation->id}",
            metadata: [
                'channel' => $identity->platform,
                'prompt_version' => $result['prompt_version'],
                'tools' => array_values(array_unique(array_column($result['tool_calls'], 'name'))),
                'input_tokens' => (int) $result['usage']['input_tokens'],
                'output_tokens' => (int) $result['usage']['output_tokens'],
                'summarised' => $newSummary !== null,
            ],
        );

        return $result['text'];
    }
}
