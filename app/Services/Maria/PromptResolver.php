<?php

namespace App\Services\Maria;

use App\Models\PromptVersion;

class PromptResolver
{
    public function active(string $type = 'maria_system'): array
    {
        $prompt = PromptVersion::query()
            ->where('prompt_type', $type)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if ($prompt) {
            return ['version' => $prompt->version, 'content' => $prompt->content];
        }

        return ['version' => 'config-v1', 'content' => (string) config('maria.system_prompt')];
    }
}
