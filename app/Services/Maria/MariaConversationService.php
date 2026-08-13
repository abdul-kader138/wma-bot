<?php

namespace App\Services\Maria;

use App\Models\AssistantChannelIdentity;
use App\Models\AssistantConversation;
use Illuminate\Support\Facades\DB;

class MariaConversationService
{
    public function __construct(private readonly MariaAgent $agent) {}

    public function handle(AssistantChannelIdentity $identity, string $message): string
    {
        $identity->loadMissing('profile.user');
        $profile = $identity->profile;

        $conversation = AssistantConversation::firstOrCreate(
            [
                'assistant_profile_id' => $profile->id,
                'assistant_channel_identity_id' => $identity->id,
                'status' => 'active',
            ],
            ['history' => []],
        );

        $history = $conversation->history ?? [];
        $result = $this->agent->handle($profile->user, $message, $history);
        $history[] = ['role' => 'user', 'content' => $message];
        $history[] = ['role' => 'assistant', 'content' => $result['text']];

        DB::transaction(function () use ($conversation, $history, $result) {
            $conversation->update([
                'history' => array_slice($history, -40),
                'last_prompt_version' => $result['prompt_version'],
                'input_tokens' => $conversation->input_tokens + (int) $result['usage']['input_tokens'],
                'output_tokens' => $conversation->output_tokens + (int) $result['usage']['output_tokens'],
                'last_message_at' => now(),
            ]);
        });

        return $result['text'];
    }
}
