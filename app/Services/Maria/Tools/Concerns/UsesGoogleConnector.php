<?php

namespace App\Services\Maria\Tools\Concerns;

use App\Models\ConnectorAccount;
use App\Models\User;
use RuntimeException;

trait UsesGoogleConnector
{
    private function googleConnector(User $owner): ConnectorAccount
    {
        return $this->findGoogleConnector($owner)
            ?? throw new RuntimeException('Google Workspace is not connected for this owner.');
    }

    private function hasGoogleConnector(User $owner): bool
    {
        return $this->findGoogleConnector($owner) !== null;
    }

    private function findGoogleConnector(User $owner): ?ConnectorAccount
    {
        return ConnectorAccount::query()
            ->where('user_id', $owner->id)
            ->where('provider', 'google')
            ->whereIn('status', ['active', 'error'])
            ->latest('id')
            ->first();
    }
}
