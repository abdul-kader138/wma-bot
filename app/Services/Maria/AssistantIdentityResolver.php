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
        return $this->resolveIdentity(
            $platform,
            $externalIdentifier,
            $channelAccountId,
        )?->profile?->user;
    }

    public function resolveIdentity(
        string $platform,
        string $externalIdentifier,
        ?int $channelAccountId = null,
    ): ?AssistantChannelIdentity {
        return AssistantChannelIdentity::resolveAuthorized($platform, $externalIdentifier, $channelAccountId);
    }
}
