<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssistantAlertResource\Pages;
use App\Models\AssistantAlert;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssistantAlertResource extends Resource
{
    protected static ?string $model = AssistantAlert::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Deadline Alerts';

    protected static ?int $navigationSort = 16;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('severity')->badge(),
            Tables\Columns\TextColumn::make('type')->badge(),
            Tables\Columns\TextColumn::make('message')->wrap()->searchable(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('first_seen_at')->dateTime('d/m/Y H:i')->sortable(),
            Tables\Columns\TextColumn::make('last_seen_at')->dateTime('d/m/Y H:i')->sortable(),
        ])->defaultSort('last_seen_at', 'desc')->filters([
            Tables\Filters\SelectFilter::make('status')->options(['active' => 'Active', 'acknowledged' => 'Acknowledged', 'resolved' => 'Resolved'])->default('active'),
        ])->actions([
            Tables\Actions\Action::make('acknowledge')->icon('heroicon-o-check')->visible(fn (AssistantAlert $record) => $record->status === 'active')->requiresConfirmation()->action(fn (AssistantAlert $record) => $record->update(['status' => 'acknowledged', 'acknowledged_at' => now()])),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAssistantAlerts::route('/')];
    }
}
