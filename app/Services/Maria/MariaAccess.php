<?php

namespace App\Services\Maria;

use App\Models\Setting;
use App\Models\User;

class MariaAccess
{
    public const PERMISSION = 'access_maria_assistant';

    public static function enabled(): bool
    {
        return filter_var(Setting::get('maria_assistant_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public static function allowed(?User $user): bool
    {
        return self::enabled() && (bool) $user && ($user->isAdmin() || $user->can(self::PERMISSION));
    }
}
