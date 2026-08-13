<?php

namespace App\Policies;

use App\Models\RelationshipRecommendation;
use App\Models\User;

class RelationshipRecommendationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RelationshipRecommendation $recommendation): bool
    {
        return $user->isAdmin() || $recommendation->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, RelationshipRecommendation $recommendation): bool
    {
        return $user->isAdmin() || $recommendation->user_id === $user->id;
    }

    public function delete(User $user, RelationshipRecommendation $recommendation): bool
    {
        return false;
    }
}
