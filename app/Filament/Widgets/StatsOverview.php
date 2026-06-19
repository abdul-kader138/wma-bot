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
            Stat::make(__('admin.stats.new_requests'), ServiceRequest::where('status', 'new')->count())
                ->description(__('admin.stats.awaiting_staff'))
                ->descriptionIcon('heroicon-m-inbox')
                ->color('danger'),

            Stat::make(__('admin.stats.in_progress'), ServiceRequest::where('status', 'in_progress')->count())
                ->description(__('admin.stats.being_handled'))
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('warning'),

            Stat::make(__('admin.stats.completed'), ServiceRequest::where('status', 'done')->count())
                ->description(__('admin.stats.all_time'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(__('admin.stats.active_conversations'), Conversation::whereIn('step', ['IN_SERVICE', 'AWAIT_LANG', 'AWAIT_SERVICE'])->count())
                ->description(__('admin.stats.open_chats'))
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('primary'),
        ];
    }
}
