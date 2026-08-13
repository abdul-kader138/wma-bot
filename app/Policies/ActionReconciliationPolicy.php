<?php

namespace App\Policies;

use App\Models\ActionReconciliation;
use App\Models\User;

class ActionReconciliationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ActionReconciliation $record): bool
    {
        return $user->isAdmin() || $record->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ActionReconciliation $record): bool
    {
        return $user->isAdmin() || $record->user_id === $user->id;
    }

    public function delete(User $user, ActionReconciliation $record): bool
    {
        return false;
    }
}
