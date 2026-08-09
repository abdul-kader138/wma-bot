<?php

namespace App\Filament\Widgets;

use App\Models\Setting;
use App\Models\WhatsAppAccount;
use App\Support\ClaudeUsageTracker;
use Filament\Widgets\Widget;

class ClaudeUsageWidget extends Widget
{
    protected static ?int $sort = 4;

    protected static ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.claude-usage';

    public function getGlobalCap(): int
    {
        return (int) Setting::get('claude_daily_global_cap', 0);
    }

    public function getGlobalCount(): int
    {
        return ClaudeUsageTracker::globalCountToday();
    }

    public function getPhoneCap(): int
    {
        return (int) Setting::get('claude_max_messages_per_day', 100);
    }

    /** Today's busiest phone numbers, with each account's display name attached. */
    public function getPhoneUsage(): array
    {
        $usage = ClaudeUsageTracker::perPhoneUsageToday();

        if ($usage === []) {
            return [];
        }

        $accountNames = WhatsAppAccount::query()
            ->whereIn('id', array_unique(array_column($usage, 'whatsapp_account_id')))
            ->pluck('name', 'id');

        return array_map(
            fn (array $row) => $row + ['account_name' => $accountNames[$row['whatsapp_account_id']] ?? null],
            $usage
        );
    }
}
