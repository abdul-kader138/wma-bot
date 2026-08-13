<?php

namespace App\Policies;

use App\Models\PromptVersion;
use App\Models\User;

class PromptVersionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, PromptVersion $prompt): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, PromptVersion $prompt): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, PromptVersion $prompt): bool
    {
        return $user->isAdmin();
    }
}
