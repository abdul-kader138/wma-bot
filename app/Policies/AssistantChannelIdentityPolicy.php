<?php

namespace App\Policies;

use App\Models\AssistantChannelIdentity;
use App\Models\User;

class AssistantChannelIdentityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, AssistantChannelIdentity $identity): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, AssistantChannelIdentity $identity): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, AssistantChannelIdentity $identity): bool
    {
        return $user->isAdmin();
    }
}
