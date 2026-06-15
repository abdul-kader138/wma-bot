<?php

namespace App\Filament\Widgets;

use App\Models\Conversation;
use App\Models\ServiceRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        return [
            Stat::make('New Requests', ServiceRequest::where('status', 'new')->count())
                ->description('Awaiting staff action')
                ->descriptionIcon('heroicon-m-inbox')
                ->color('danger'),

            Stat::make('In Progress', ServiceRequest::where('status', 'in_progress')->count())
                ->description('Being handled')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('warning'),

            Stat::make('Completed', ServiceRequest::where('status', 'done')->count())
                ->description('All time')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Active Conversations', Conversation::whereIn('step', ['IN_SERVICE', 'AWAIT_LANG', 'AWAIT_SERVICE'])->count())
                ->description('Currently open chats')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('primary'),
        ];
    }
}
