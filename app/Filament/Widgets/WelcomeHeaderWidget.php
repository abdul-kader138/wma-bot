<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class WelcomeHeaderWidget extends Widget
{
    protected static ?int $sort = -1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.welcome-header';

    public function getUser(): mixed
    {
        return Filament::auth()->user();
    }

    public function getGreeting(): string
    {
        $hour = now()->hour;

        if ($hour < 12) {
            return __('admin.dashboard.greeting.morning');
        } elseif ($hour < 17) {
            return __('admin.dashboard.greeting.afternoon');
        }

        return __('admin.dashboard.greeting.evening');
    }
}
