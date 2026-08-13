<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommunicationResource\Pages;
use App\Models\Communication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommunicationResource extends Resource
{
    protected static ?string $model = Communication::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Email Triage';

    protected static ?int $navigationSort = 14;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('classification')->disabled(),
            Forms\Components\TextInput::make('sensitivity')->disabled(),
            Forms\Components\Textarea::make('summary')->disabled()->columnSpanFull(),
            Forms\Components\KeyValue::make('commitments')->disabled()->columnSpanFull(),
            Forms\Components\Textarea::make('draft_response')->label('Draft reply (not sent)')->rows(10)->columnSpanFull(),
            Forms\Components\DateTimePicker::make('follow_up_at')->displayFormat('d/m/Y H:i'),
            Forms\Components\TextInput::make('source_url')->disabled()->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('source_metadata.subject')->label('Subject')->limit(60),
            Tables\Columns\TextColumn::make('source_metadata.from')->label('From')->limit(40),
            Tables\Columns\TextColumn::make('classification')->badge(),
            Tables\Columns\TextColumn::make('sensitivity')->badge(),
            Tables\Columns\IconColumn::make('draft_response')->label('Draft')->boolean(),
            Tables\Columns\TextColumn::make('follow_up_at')->dateTime('d/m/Y H:i')->sortable(),
        ])->defaultSort('created_at', 'desc')->actions([Tables\Actions\EditAction::make()]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCommunications::route('/'), 'edit' => Pages\EditCommunication::route('/{record}/edit')];
    }
}
