<?php

namespace App\Filament\Widgets;

use App\Models\ServiceRequest;
use Filament\Widgets\ChartWidget;

class ServiceRequestsChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2;

    public ?string $filter = '14';

    public function getHeading(): ?string
    {
        return __('admin.dashboard.chart.heading');
    }

    protected function getFilters(): ?array
    {
        return [
            '7'  => __('admin.dashboard.chart.last_7_days'),
            '14' => __('admin.dashboard.chart.last_14_days'),
            '30' => __('admin.dashboard.chart.last_30_days'),
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

        $counts = ServiceRequest::where('created_at', '>=', $start)
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
                'label'           => __('admin.dashboard.chart.dataset_label'),
                'data'            => $data,
                'backgroundColor' => 'rgba(245, 158, 11, 0.65)',
                'borderColor'     => 'rgb(245, 158, 11)',
                'borderWidth'     => 1,
                'borderRadius'    => 4,
            ]],
            'labels' => $labels,
        ];
    }
}
