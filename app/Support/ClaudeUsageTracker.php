<?php

namespace App\Support;

use App\Models\ClaudeDailyUsage;

/**
 * Reads the daily Claude usage counters that HandleIncomingMessage writes to the
 * claude_daily_usages table, so the admin usage widget shows exactly what's gating
 * customers instead of a separate, driftable count.
 *
 * Backed by a real table rather than cache internals on purpose: the per-minute
 * burst limiter can stay opaque cache counters since nothing needs to enumerate it,
 * but the daily caps back an admin dashboard that has to list "which phone numbers
 * used how much today" — that's not something Redis (the production cache store)
 * exposes cheaply, so it lives in the database instead, identically on every
 * cache driver.
 */
class ClaudeUsageTracker
{
    public static function globalCountToday(): int
    {
        return (int) ClaudeDailyUsage::whereDate('date', today())->sum('count');
    }

    /** Today's busiest phone numbers, busiest first: [['whatsapp_account_id', 'phone', 'count'], ...]. */
    public static function perPhoneUsageToday(int $limit = 20): array
    {
        return ClaudeDailyUsage::whereDate('date', today())
            ->orderByDesc('count')
            ->limit($limit)
            ->get(['whatsapp_account_id', 'phone', 'count'])
            ->map(fn (ClaudeDailyUsage $row) => [
                'whatsapp_account_id' => $row->whatsapp_account_id,
                'phone'               => $row->phone,
                'count'               => $row->count,
            ])
            ->all();
    }
}
