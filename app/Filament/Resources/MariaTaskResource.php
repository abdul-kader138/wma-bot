<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MariaTaskResource\Pages;
use App\Models\MariaTask;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MariaTaskResource extends MariaResource
{
    protected static ?string $model = MariaTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?int $navigationSort = 11;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('project_id')->relationship('project', 'name', fn (Builder $query) => auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id()))->searchable()->preload(),
            Forms\Components\Textarea::make('task')->required()->columnSpanFull(),
            Forms\Components\TextInput::make('owner_name')->required(),
            Forms\Components\Select::make('source')->options(['user' => 'User', 'email' => 'Email', 'meeting' => 'Meeting', 'document' => 'Document', 'automation' => 'Automation'])->required(),
            Forms\Components\DatePicker::make('due_at')->displayFormat(config('app.display_date_format', 'd/m/Y')),
            Forms\Components\DatePicker::make('follow_up_at')->displayFormat(config('app.display_date_format', 'd/m/Y')),
            Forms\Components\Select::make('status')->options(['open' => 'Open', 'scheduled' => 'Scheduled', 'waiting' => 'Waiting', 'blocked' => 'Blocked', 'duplicate_review' => 'Possible Duplicate', 'completed' => 'Completed'])->required(),
            Forms\Components\TextInput::make('priority_score')->numeric()->disabled(),
            Forms\Components\Textarea::make('priority_reason')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('task')->limit(70)->searchable(),
            Tables\Columns\TextColumn::make('project.name')->placeholder('—'),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('priority_score')->sortable(),
            Tables\Columns\TextColumn::make('due_at')->date(config('app.display_date_format', 'd/m/Y'))->sortable(),
        ])->defaultSort('due_at')->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListMariaTasks::route('/'), 'create' => Pages\CreateMariaTask::route('/create'), 'edit' => Pages\EditMariaTask::route('/{record}/edit')];
    }
}
