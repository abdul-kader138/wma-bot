<?php

namespace App\Services\Maria;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ExternalActionGuard
{
    public function assertEnabled(User $owner): void
    {
        if (! Setting::get('maria_external_actions_enabled', true)) {
            throw ValidationException::withMessages(['external_actions' => 'All Maria external actions are stopped by the emergency switch.']);
        }
        $profile = $owner->assistantProfile;
        if ($profile && ! $profile->external_actions_enabled) {
            throw ValidationException::withMessages(['external_actions' => 'External actions are disabled for this Maria profile.']);
        }
    }
}
