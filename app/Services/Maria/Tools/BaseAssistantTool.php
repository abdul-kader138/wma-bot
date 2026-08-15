<?php

namespace App\Services\Maria\Tools;

use App\Models\User;

abstract class BaseAssistantTool implements AssistantTool
{
    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'input_schema' => $this->inputSchema(),
        ];
    }

    public function requiresApproval(): bool
    {
        return false;
    }

    public function availableFor(User $owner): bool
    {
        return true;
    }
}
