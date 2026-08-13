<?php

namespace App\Policies;

use App\Models\ContentItem;
use App\Models\User;

class ContentItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ContentItem $item): bool
    {
        return $user->isAdmin() || $item->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ContentItem $item): bool
    {
        return $user->isAdmin() || $item->user_id === $user->id;
    }

    public function delete(User $user, ContentItem $item): bool
    {
        return $user->isAdmin() || $item->user_id === $user->id;
    }
}
