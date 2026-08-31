<?php

namespace App\Services\Maria;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ExternalActionGuard
{
    /**
     * The global external-action switch fails safe: anything other than an
     * explicit true (missing row, cache miss, "0"/"false") counts as stopped.
     * An admin turns it on deliberately via External Action Control.
     */
    public static function globallyEnabled(): bool
    {
        return filter_var(Setting::get('maria_external_actions_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function assertEnabled(User $owner): void
    {
        if (! self::globallyEnabled()) {
            throw ValidationException::withMessages(['external_actions' => 'All Maria external actions are stopped by the emergency switch.']);
        }
        $profile = $owner->assistantProfile;
        if ($profile && ! $profile->external_actions_enabled) {
            throw ValidationException::withMessages(['external_actions' => 'External actions are disabled for this Maria profile.']);
        }
    }
}
