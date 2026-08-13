<?php

namespace App\Policies;

use App\Models\AssistantAlert;
use App\Models\User;

class AssistantAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AssistantAlert $alert): bool
    {
        return $user->isAdmin() || $alert->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AssistantAlert $alert): bool
    {
        return $user->isAdmin() || $alert->user_id === $user->id;
    }

    public function delete(User $user, AssistantAlert $alert): bool
    {
        return false;
    }
}
