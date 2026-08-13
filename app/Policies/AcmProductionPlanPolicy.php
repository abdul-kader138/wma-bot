<?php

namespace App\Policies;

use App\Models\AcmProductionPlan;
use App\Models\User;

class AcmProductionPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AcmProductionPlan $plan): bool
    {
        return $user->isAdmin() || $plan->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AcmProductionPlan $plan): bool
    {
        return $user->isAdmin() || $plan->user_id === $user->id;
    }

    public function delete(User $user, AcmProductionPlan $plan): bool
    {
        return $user->isAdmin() || $plan->user_id === $user->id;
    }
}
