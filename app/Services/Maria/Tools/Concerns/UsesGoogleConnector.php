<?php

namespace App\Services\Maria\Tools\Concerns;

use App\Models\ConnectorAccount;
use App\Models\User;
use RuntimeException;

trait UsesGoogleConnector
{
    private function googleConnector(User $owner): ConnectorAccount
    {
        return ConnectorAccount::query()
            ->where('user_id', $owner->id)
            ->where('provider', 'google')
            ->whereIn('status', ['active', 'error'])
            ->latest('id')
            ->first() ?? throw new RuntimeException('Google Workspace is not connected for this owner.');
    }
}
