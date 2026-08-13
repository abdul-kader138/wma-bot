<?php

namespace App\Filament\Pages;

use App\Services\Maria\AcceptanceMetricsService;
use App\Services\Maria\MariaAccess;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MariaAcceptanceDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Acceptance Dashboard';

    protected static ?int $navigationSort = 28;

    protected static string $view = 'filament.pages.maria-acceptance-dashboard';

    public static function canAccess(): bool
    {
        return MariaAccess::allowed(auth()->user());
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('save_snapshot')->label('Save 30-day snapshot')->icon('heroicon-o-camera')->action(function () {
            app(AcceptanceMetricsService::class)->snapshot(auth()->user());
            Notification::make()->title('Auditable 30-day snapshot saved')->success()->send();
        })];
    }

    public function getViewData(): array
    {
        return ['report' => app(AcceptanceMetricsService::class)->calculate(auth()->user())];
    }
}
