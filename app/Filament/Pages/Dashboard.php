<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ClaudeUsageWidget;
use App\Filament\Widgets\ConversationsChartWidget;
use App\Filament\Widgets\RecentServiceRequestsWidget;
use App\Filament\Widgets\ServiceRequestsChartWidget;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\WelcomeHeaderWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            WelcomeHeaderWidget::class,
            StatsOverview::class,
            ServiceRequestsChartWidget::class,
            RecentServiceRequestsWidget::class,
            ConversationsChartWidget::class,
            ClaudeUsageWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 3;
    }
}
