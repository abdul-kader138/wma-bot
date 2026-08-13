<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait OwnsMariaRecords
{
    public function viewAny(User $user): bool
    {
        return $this->canUseMaria($user);
    }

    public function view(User $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    public function create(User $user): bool
    {
        return $this->canUseMaria($user);
    }

    public function update(User $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    public function restoreAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return $user->isAdmin() && $this->owns($user, $record);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function replicate(User $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    public function reorder(User $user): bool
    {
        return false;
    }

    private function owns(User $user, Model $record): bool
    {
        return $user->isAdmin() || (int) $record->getAttribute('user_id') === $user->id;
    }

    private function canUseMaria(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'panel_user']) || $user->getAllPermissions()->isNotEmpty();
    }
}
