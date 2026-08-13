<?php

namespace App\Policies;

use App\Models\AgverseOpportunity;
use App\Models\User;

class AgverseOpportunityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AgverseOpportunity $opportunity): bool
    {
        return $user->isAdmin() || $opportunity->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AgverseOpportunity $opportunity): bool
    {
        return $user->isAdmin() || $opportunity->user_id === $user->id;
    }

    public function delete(User $user, AgverseOpportunity $opportunity): bool
    {
        return $user->isAdmin() || $opportunity->user_id === $user->id;
    }
}
