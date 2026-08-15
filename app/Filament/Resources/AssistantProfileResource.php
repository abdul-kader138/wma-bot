<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssistantProfileResource\Pages;
use App\Models\AssistantProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class AssistantProfileResource extends MariaResource
{
    protected static ?string $model = AssistantProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Maria Settings';

    protected static ?int $navigationSort = 18;

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
            Forms\Components\Select::make('weekly_production_day')->options([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday']),
            Forms\Components\TimePicker::make('weekly_production_at'),
            Forms\Components\CheckboxList::make('enabled_workflows')->options([
                'morning_brief' => 'Morning Brief', 'evening_review' => 'Evening Review',
                'email_triage' => 'Email Triage', 'meeting_preparation' => 'Meeting Preparation',
                'deadline_monitor' => 'Deadline Monitor',
                'daily_five' => 'Daily Five Relationships',
                'book_portfolio_review' => 'Weekly Book Portfolio Review',
                'agverse_opportunity_review' => 'Thursday Agverse Opportunity Review',
                'acm_weekly_production' => 'All Catholic Media Weekly Production',
                'quality_report' => 'Weekly Maria Quality Report',
            ])->columnSpanFull(),
            Forms\Components\Textarea::make('voice_preferences')->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')->required(),
            Forms\Components\Toggle::make('external_actions_enabled')->label('Allow approved external actions')->helperText('Emergency per-profile switch. Exact approvals are still required.'),
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
