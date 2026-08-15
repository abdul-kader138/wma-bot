<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConnectorAccountResource\Pages;
use App\Models\ConnectorAccount;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class ConnectorAccountResource extends MariaResource
{
    protected static ?string $model = ConnectorAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Connections';

    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('provider')->badge(),
            Tables\Columns\TextColumn::make('email'),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('scopes')->formatStateUsing(fn ($state) => count((array) $state).' permissions'),
            Tables\Columns\TextColumn::make('last_synced_at')->dateTime(config('app.display_datetime_format', 'd/m/Y H:i'))->placeholder('Never'),
            Tables\Columns\TextColumn::make('last_error')->limit(50)->toggleable(),
        ])->actions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListConnectorAccounts::route('/')];
    }
}
