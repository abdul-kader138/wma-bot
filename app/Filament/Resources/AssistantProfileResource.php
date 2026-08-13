<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssistantProfileResource\Pages;
use App\Models\AssistantProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssistantProfileResource extends Resource
{
    protected static ?string $model = AssistantProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Maria Settings';

    protected static ?int $navigationSort = 18;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name.' · '.$record->email)
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn () => auth()->user()?->isAdmin()),
            Forms\Components\Select::make('timezone')->searchable()->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))->required(),
            Forms\Components\Select::make('language')->options(['en' => 'English', 'it' => 'Italiano', 'bn' => 'বাংলা'])->required(),
            Forms\Components\TimePicker::make('working_hours_start'), Forms\Components\TimePicker::make('working_hours_end'),
            Forms\Components\TimePicker::make('morning_brief_at'), Forms\Components\TimePicker::make('evening_review_at'),
            Forms\Components\CheckboxList::make('enabled_workflows')->options([
                'morning_brief' => 'Morning Brief', 'evening_review' => 'Evening Review',
                'email_triage' => 'Email Triage', 'meeting_preparation' => 'Meeting Preparation',
                'deadline_monitor' => 'Deadline Monitor',
                'daily_five' => 'Daily Five Relationships',
            ])->columnSpanFull(),
            Forms\Components\Textarea::make('voice_preferences')->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('user.name'), Tables\Columns\TextColumn::make('timezone'),
            Tables\Columns\TextColumn::make('morning_brief_at'), Tables\Columns\TextColumn::make('evening_review_at'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAssistantProfiles::route('/'), 'create' => Pages\CreateAssistantProfile::route('/create'), 'edit' => Pages\EditAssistantProfile::route('/{record}/edit')];
    }
}
