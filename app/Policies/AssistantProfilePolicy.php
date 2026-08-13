<?php

namespace App\Policies;

use App\Models\AssistantProfile;
use App\Models\User;

class AssistantProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AssistantProfile $profile): bool
    {
        return $user->isAdmin() || $profile->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || ! $user->assistantProfile()->exists();
    }

    public function update(User $user, AssistantProfile $profile): bool
    {
        return $user->isAdmin() || $profile->user_id === $user->id;
    }

    public function delete(User $user, AssistantProfile $profile): bool
    {
        return false;
    }
}
