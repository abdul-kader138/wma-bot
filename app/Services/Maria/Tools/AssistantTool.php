<?php

namespace App\Services\Maria\Tools;

use App\Models\User;

interface AssistantTool
{
    public function name(): string;

    public function description(): string;

    public function inputSchema(): array;

    public function requiresApproval(): bool;

    public function execute(User $owner, array $input): array;

    public function definition(): array;
}
