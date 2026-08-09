<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Reads the daily Claude usage counters that HandleIncomingMessage writes via
 * Laravel's RateLimiter (see its circuitBreakerTripped()/dailyLimitReached()),
 * so the admin usage widget shows exactly what's gating customers instead of a
 * separate, driftable count.
 */
class ClaudeUsageTracker
{
    public const GLOBAL_KEY = 'claude-daily-global';

    public static function phoneKey(int $whatsAppAccountId, string $phone): string
    {
        return "claude-daily:{$whatsAppAccountId}:{$phone}";
    }

    public static function globalCountToday(): int
    {
        return RateLimiter::attempts(self::GLOBAL_KEY);
    }

    /**
     * Today's per-phone counters, busiest first: [['whatsapp_account_id', 'phone', 'count'], ...].
     *
     * Only supported on the 'database' cache driver — Laravel's Cache facade has no
     * "keys matching a pattern" lookup, so discovering which phone keys exist today
     * means reading the store's table directly. Each count is then re-read through
     * RateLimiter (not parsed from the raw row) so it matches exactly what gates calls.
     */
    public static function perPhoneUsageToday(int $limit = 20): array
    {
        if (config('cache.default') !== 'database') {
            return [];
        }

        $table  = config('cache.stores.database.table', 'cache');
        $prefix = (string) config('cache.prefix', '');

        $keys = DB::table($table)
            ->where('key', 'like', $prefix.'claude-daily:%')
            ->where('key', 'not like', '%:timer')
            ->where('expiration', '>', now()->timestamp)
            ->pluck('key');

        $usage = [];

        foreach ($keys as $rawKey) {
            $key   = Str::after($rawKey, $prefix);
            $parts = explode(':', Str::after($key, 'claude-daily:'), 2);

            if (count($parts) !== 2) {
                continue;
            }

            [$accountId, $phone] = $parts;

            $usage[] = [
                'whatsapp_account_id' => (int) $accountId,
                'phone'               => $phone,
                'count'               => RateLimiter::attempts($key),
            ];
        }

        usort($usage, fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_slice($usage, 0, $limit);
    }
}
