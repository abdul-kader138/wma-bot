<?php

namespace App\Services\Maria;

use App\Models\MariaEvalRun;
use App\Models\Setting;
use Illuminate\Validation\ValidationException;

/**
 * Blocks activation of a Maria system prompt until it has a recent passing
 * behavioural eval, when the eval gate is switched on. Any other prompt type,
 * and the case where the gate is off, pass straight through.
 */
class PromptActivationGuard
{
    public function assertEvalPassed(string $promptType, string $contentHash): void
    {
        if ($promptType !== 'maria_system') {
            return;
        }

        if (! filter_var(Setting::get('maria_eval_gate_enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $minPassRate = (float) Setting::get('maria_eval_min_pass_rate', 100);

        $hasPassingRun = MariaEvalRun::query()
            ->where('prompt_type', 'maria_system')
            ->where('prompt_content_hash', $contentHash)
            ->where('pass_rate', '>=', $minPassRate)
            ->where('created_at', '>=', now()->subDays(30))
            ->exists();

        if (! $hasPassingRun) {
            throw ValidationException::withMessages([
                'is_active' => "This prompt cannot be activated: no `maria:eval --live` run scoring at least {$minPassRate}% "
                    .'for its exact text in the last 30 days. Run `php artisan maria:eval --live --prompt-version=<this version>` first.',
            ]);
        }
    }
}
