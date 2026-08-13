<?php

namespace App\Services\Maria;

use App\Models\AssistantChannelIdentity;
use App\Models\User;

class AssistantIdentityResolver
{
    public function resolve(
        string $platform,
        string $externalIdentifier,
        ?int $channelAccountId = null,
    ): ?User {
        $user = $this->resolveIdentity(
            $platform,
            $externalIdentifier,
            $channelAccountId,
        )?->profile?->user;

        return MariaAccess::allowed($user) ? $user : null;
    }

    public function resolveIdentity(
        string $platform,
        string $externalIdentifier,
        ?int $channelAccountId = null,
    ): ?AssistantChannelIdentity {
        if (! MariaAccess::enabled()) {
            return null;
        }

        return AssistantChannelIdentity::resolveAuthorized($platform, $externalIdentifier, $channelAccountId);
    }
}
