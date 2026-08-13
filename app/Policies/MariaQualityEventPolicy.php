<?php

namespace App\Policies;

use App\Models\MariaQualityEvent;
use App\Models\User;

class MariaQualityEventPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MariaQualityEvent $event): bool
    {
        return $user->isAdmin() || $event->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MariaQualityEvent $event): bool
    {
        return $user->isAdmin() || $event->user_id === $user->id;
    }

    public function delete(User $user, MariaQualityEvent $event): bool
    {
        return $user->isAdmin() || $event->user_id === $user->id;
    }
}
