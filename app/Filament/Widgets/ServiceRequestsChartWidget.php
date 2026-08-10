<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\BreaksDownByAccount;
use App\Models\ServiceRequest;
use Filament\Widgets\ChartWidget;

class ServiceRequestsChartWidget extends ChartWidget
{
    use BreaksDownByAccount;

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '30s';

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

        $labels   = [];
        $dateKeys = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $dateKeys[] = now()->subDays($i)->format('Y-m-d');
            $labels[]   = now()->subDays($i)->format($days > 14 ? 'M d' : 'd M');
        }

        return [
            'datasets' => $this->accountDatasets(
                ServiceRequest::where('created_at', '>=', $start),
                $dateKeys,
            ),
            'labels' => $labels,
        ];
    }
}
