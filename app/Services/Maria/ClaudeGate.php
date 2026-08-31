<?php

namespace App\Services\Maria;

use App\Models\ClaudeDailyUsage;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * One choke point for every Claude request Maria makes — the conversational
 * agent and every scheduled workflow. It enforces two budgets before a call:
 *
 *  1. a per-minute burst limit shared with the public WhatsApp bot (one Anthropic
 *     key for the whole app), so an inbox-triage storm or a scheduler misfire
 *     cannot quietly starve customer conversations of API capacity; and
 *  2. two daily ceilings — the app-wide `claude_daily_global_cap` (also read by
 *     the bot's circuit breaker) and a Maria-only `maria_daily_call_cap` — so
 *     total spend has a hard stop, not just a smoothed rate.
 *
 * It is deliberately not the bot's per-customer session caps: those contain an
 * abusive stranger, which does not apply to an allowlisted internal owner.
 */
class ClaudeGate
{
    /** Shared with App\Jobs\HandleIncomingMessage so both paths draw the same per-minute budget. */
    private const MINUTE_KEY = 'claude-api-calls';

    /**
     * Reserve one Claude call. Call immediately before each HTTP request.
     *
     * @throws ClaudeRateLimited when a per-minute or daily budget is spent
     */
    public function reserve(): void
    {
        $this->assertWithinDailyCaps();

        $maxPerMinute = max(1, (int) Setting::get('claude_rate_limit_per_minute', 50));
        if (RateLimiter::tooManyAttempts(self::MINUTE_KEY, $maxPerMinute)) {
            throw new ClaudeRateLimited(max(1, RateLimiter::availableIn(self::MINUTE_KEY)));
        }

        RateLimiter::hit(self::MINUTE_KEY, 60);
        $this->countMariaCall();
    }

    /** Maria's own Claude calls used so far today (for observability / dashboards). */
    public static function mariaCallsToday(): int
    {
        return (int) Cache::get(self::dailyKey(), 0);
    }

    private function assertWithinDailyCaps(): void
    {
        $globalCap = (int) Setting::get('claude_daily_global_cap', 0);
        if ($globalCap > 0 && (int) ClaudeDailyUsage::whereDate('date', today())->sum('count') >= $globalCap) {
            throw new ClaudeRateLimited($this->secondsUntilMidnight());
        }

        $mariaCap = (int) Setting::get('maria_daily_call_cap', 0);
        if ($mariaCap > 0 && self::mariaCallsToday() >= $mariaCap) {
            throw new ClaudeRateLimited($this->secondsUntilMidnight());
        }
    }

    private function countMariaCall(): void
    {
        $key = self::dailyKey();
        Cache::add($key, 0, now()->endOfDay());
        Cache::increment($key);
    }

    private function secondsUntilMidnight(): int
    {
        return (int) max(1, round(abs(now()->diffInSeconds(now()->endOfDay()))));
    }

    private static function dailyKey(): string
    {
        return 'maria-claude-calls:'.today()->toDateString();
    }
}
