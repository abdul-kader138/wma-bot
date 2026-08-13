<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MariaContactResource\Pages;
use App\Models\MariaContact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MariaContactResource extends MariaResource
{
    protected static ?string $model = MariaContact::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Contacts';

    protected static ?int $navigationSort = 16;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('full_name')->required(),
            Forms\Components\TextInput::make('email')->email(),
            Forms\Components\TextInput::make('role'), Forms\Components\TextInput::make('organization'),
            Forms\Components\Select::make('tier')->options(['A' => 'A', 'B' => 'B', 'C' => 'C']),
            Forms\Components\Select::make('stage')->options(array_combine($stages = ['research', 'engage', 'connect', 'conversation', 'meeting', 'opportunity', 'partner', 'dormant'], $stages))->required(),
            Forms\Components\Textarea::make('why_matters')->columnSpanFull(),
            Forms\Components\Textarea::make('verification_source')->columnSpanFull(),
            Forms\Components\Textarea::make('warm_path')->columnSpanFull(),
            Forms\Components\Textarea::make('next_action')->columnSpanFull(),
            Forms\Components\DatePicker::make('follow_up_at')->displayFormat(config('app.display_date_format', 'd/m/Y')),
            Forms\Components\Textarea::make('consent_preferences')->columnSpanFull(),
            Forms\Components\Textarea::make('private_contact_data')->columnSpanFull(),
            Forms\Components\Textarea::make('notes')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('full_name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('email')->searchable(), Tables\Columns\TextColumn::make('organization')->searchable(),
            Tables\Columns\TextColumn::make('tier')->badge(), Tables\Columns\TextColumn::make('stage')->badge(),
            Tables\Columns\TextColumn::make('follow_up_at')->date(config('app.display_date_format', 'd/m/Y'))->sortable(),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListMariaContacts::route('/'), 'create' => Pages\CreateMariaContact::route('/create'), 'edit' => Pages\EditMariaContact::route('/{record}/edit')];
    }
}
