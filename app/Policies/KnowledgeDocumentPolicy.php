<?php

namespace App\Policies;

use App\Models\KnowledgeDocument;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class KnowledgeDocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_knowledge::document');
    }

    public function view(User $user, KnowledgeDocument $knowledgeDocument): bool
    {
        return $user->can('view_knowledge::document');
    }

    public function create(User $user): bool
    {
        return $user->can('create_knowledge::document');
    }

    public function update(User $user, KnowledgeDocument $knowledgeDocument): bool
    {
        return $user->can('update_knowledge::document');
    }

    public function delete(User $user, KnowledgeDocument $knowledgeDocument): bool
    {
        return $user->can('delete_knowledge::document');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_knowledge::document');
    }

    public function forceDelete(User $user, KnowledgeDocument $knowledgeDocument): bool
    {
        return $user->can('force_delete_knowledge::document');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_knowledge::document');
    }

    public function restore(User $user, KnowledgeDocument $knowledgeDocument): bool
    {
        return $user->can('restore_knowledge::document');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_knowledge::document');
    }

    public function replicate(User $user, KnowledgeDocument $knowledgeDocument): bool
    {
        return $user->can('replicate_knowledge::document');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_knowledge::document');
    }
}
