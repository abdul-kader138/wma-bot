<?php

namespace App\Filament\Pages;

use App\Services\Maria\MariaAccess;
use App\Services\Maria\MariaMetricsService;
use Filament\Pages\Page;

class MariaMetrics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Cost & Quality';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.maria-metrics';

    public int $days = 30;

    public static function canAccess(): bool
    {
        return MariaAccess::allowed(auth()->user());
    }

    public function getViewData(): array
    {
        $isAdmin = (bool) auth()->user()?->isAdmin();

        return [
            'days' => $this->days,
            'metrics' => app(MariaMetricsService::class)->summary(
                $this->days,
                $isAdmin ? null : auth()->id(),
            ),
            'scope' => $isAdmin ? 'all owners' : 'your workflows',
        ];
    }
}
