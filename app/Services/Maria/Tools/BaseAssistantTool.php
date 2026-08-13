<?php

namespace App\Services\Maria\Tools;

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
}
