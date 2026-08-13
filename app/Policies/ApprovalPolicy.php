<?php

namespace App\Policies;

use App\Models\Approval;
use App\Models\User;
use App\Policies\Concerns\OwnsMariaRecords;

class ApprovalPolicy
{
    use OwnsMariaRecords;

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Approval $approval): bool
    {
        return false;
    }

    public function delete(User $user, Approval $approval): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
