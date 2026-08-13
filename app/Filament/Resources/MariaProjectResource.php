<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MariaProjectResource\Pages;
use App\Models\MariaProject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MariaProjectResource extends Resource
{
    protected static ?string $model = MariaProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Outcome')->schema([
                Forms\Components\Select::make('domain')->options(self::domains())->required(),
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Textarea::make('desired_outcome')->required()->columnSpanFull(),
                Forms\Components\Select::make('stage')->options(array_combine(
                    $values = ['idea', 'defined', 'active', 'waiting', 'review', 'complete', 'paused', 'archived'], $values
                ))->required(),
                Forms\Components\Select::make('priority')->options(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'critical' => 'Critical'])->required(),
                Forms\Components\Select::make('confidentiality')->options(['public' => 'Public', 'internal' => 'Internal', 'confidential' => 'Confidential', 'restricted' => 'Restricted'])->required(),
                Forms\Components\TextInput::make('owner_name')->required(),
                Forms\Components\Textarea::make('next_action')->required()->columnSpanFull(),
                Forms\Components\DateTimePicker::make('next_action_at')->required(),
                Forms\Components\DateTimePicker::make('deadline_at'),
                Forms\Components\Select::make('status')->options(['completed' => 'Completed', 'awaiting_approval' => 'Awaiting Approval', 'waiting' => 'Waiting on Another Person', 'scheduled' => 'Scheduled', 'blocked' => 'Blocked'])->required(),
                Forms\Components\Textarea::make('blocker')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('domain')->badge(),
            Tables\Columns\TextColumn::make('priority')->badge(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('next_action_at')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('deadline_at')->dateTime()->sortable(),
        ])->defaultSort('next_action_at')->actions([
            Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListMariaProjects::route('/'), 'create' => Pages\CreateMariaProject::route('/create'), 'edit' => Pages\EditMariaProject::route('/{record}/edit')];
    }

    public static function domains(): array
    {
        return ['PER' => 'Personal Executive', 'ACM' => 'All Catholic Media', 'AGV' => 'Agverse AI UAE', 'BKS' => 'Books and Publishing', 'REL' => 'Relationships', 'COM' => 'Communications', 'MTG' => 'Meetings', 'LEG' => 'Legal Administration', 'XPF' => 'Cross-Portfolio'];
    }
}
