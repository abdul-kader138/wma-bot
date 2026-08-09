<?php

namespace App\Filament\Widgets;

use App\Models\Conversation;
use Filament\Widgets\ChartWidget;

class ConversationsChartWidget extends ChartWidget
{
    protected static ?int $sort = 5;

    protected static ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '14';

    public function getHeading(): ?string
    {
        return __('admin.dashboard.conversations_chart.heading');
    }

    protected function getFilters(): ?array
    {
        return [
            '7'  => __('admin.dashboard.conversations_chart.last_7_days'),
            '14' => __('admin.dashboard.conversations_chart.last_14_days'),
            '30' => __('admin.dashboard.conversations_chart.last_30_days'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $days  = (int) ($this->filter ?? 14);
        $start = now()->subDays($days - 1)->startOfDay();

        $counts = Conversation::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $labels = [];
        $data   = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date     = now()->subDays($i)->format('Y-m-d');
            $label    = now()->subDays($i)->format($days > 14 ? 'M d' : 'd M');
            $labels[] = $label;
            $data[]   = $counts->get($date, 0);
        }

        return [
            'datasets' => [[
                'label'           => __('admin.dashboard.conversations_chart.dataset_label'),
                'data'            => $data,
                'backgroundColor' => 'rgba(59, 130, 246, 0.65)',
                'borderColor'     => 'rgb(59, 130, 246)',
                'borderWidth'     => 1,
                'borderRadius'    => 4,
            ]],
            'labels' => $labels,
        ];
    }
}
