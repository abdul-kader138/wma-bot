<?php

namespace App\Filament\Widgets;

use App\Models\ServiceRequest;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentServiceRequestsWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('admin.dashboard.recent.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ServiceRequest::query()->latest()->limit(8)
            )
            ->columns([
                TextColumn::make('wa_phone')
                    ->label(__('admin.service_request.fields.phone_short'))
                    ->searchable(false)
                    ->limit(15),

                BadgeColumn::make('service')
                    ->label(__('admin.service_request.fields.service'))
                    ->colors([
                        'primary' => 'ticket',
                        'warning' => 'license',
                        'success' => 'immigration',
                    ]),

                BadgeColumn::make('status')
                    ->label(__('admin.service_request.fields.status'))
                    ->formatStateUsing(fn (string $state) => __("admin.service_request.status.{$state}"))
                    ->colors([
                        'danger'  => 'new',
                        'warning' => 'in_progress',
                        'success' => 'done',
                    ]),

                TextColumn::make('created_at')
                    ->label(__('admin.service_request.fields.received'))
                    ->since()
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
