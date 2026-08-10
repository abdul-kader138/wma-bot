<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\WhatsAppAccount;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared by ConversationsChartWidget and ServiceRequestsChartWidget — turns a
 * date-ranged query into one stacked bar-chart dataset per messaging account, so the
 * daily total is still readable at a glance while showing which account (a WhatsApp
 * number, a Messenger page, an IG account, ...) it's made up of.
 */
trait BreaksDownByAccount
{
    /**
     * @param  array<string>  $dateKeys  Y-m-d keys for every day in the chart range, in order.
     * @return array<int, array<string, mixed>>
     */
    private function accountDatasets(Builder $query, array $dateKeys): array
    {
        $rows = $query
            ->selectRaw('DATE(created_at) as date, whatsapp_account_id, COUNT(*) as count')
            ->groupBy('date', 'whatsapp_account_id')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $accountNames = WhatsAppAccount::query()
            ->whereIn('id', $rows->pluck('whatsapp_account_id')->unique())
            ->pluck('name', 'id');

        $datasets = [];
        $index    = 0;

        foreach ($rows->groupBy('whatsapp_account_id') as $accountId => $accountRows) {
            $countsByDate = $accountRows->pluck('count', 'date');
            $color        = $this->accountPaletteColor($index++);

            $datasets[] = [
                'label'           => $accountNames[$accountId] ?? "Account #{$accountId}",
                'data'            => collect($dateKeys)->map(fn ($d) => (int) $countsByDate->get($d, 0))->all(),
                'backgroundColor' => $color['bg'],
                'borderColor'     => $color['border'],
                'borderWidth'     => 1,
                'borderRadius'    => 4,
            ];
        }

        return $datasets;
    }

    /**
     * @return array{bg: string, border: string}
     */
    private function accountPaletteColor(int $index): array
    {
        static $colors = [
            ['bg' => 'rgba(59, 130, 246, 0.75)', 'border' => 'rgb(59, 130, 246)'],  // blue
            ['bg' => 'rgba(245, 158, 11, 0.75)', 'border' => 'rgb(245, 158, 11)'],  // amber
            ['bg' => 'rgba(99, 102, 241, 0.75)', 'border' => 'rgb(99, 102, 241)'],  // indigo
            ['bg' => 'rgba(16, 185, 129, 0.75)', 'border' => 'rgb(16, 185, 129)'],  // green
            ['bg' => 'rgba(236, 72, 153, 0.75)', 'border' => 'rgb(236, 72, 153)'],  // pink
            ['bg' => 'rgba(20, 184, 166, 0.75)', 'border' => 'rgb(20, 184, 166)'],  // teal
            ['bg' => 'rgba(168, 85, 247, 0.75)', 'border' => 'rgb(168, 85, 247)'],  // purple
            ['bg' => 'rgba(239, 68, 68, 0.75)',  'border' => 'rgb(239, 68, 68)'],   // red
        ];

        return $colors[$index % count($colors)];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['stacked' => true],
                'y' => ['stacked' => true],
            ],
        ];
    }
}
