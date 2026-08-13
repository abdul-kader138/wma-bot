<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgverseOpportunityResource\Pages;
use App\Models\AgverseOpportunity;
use App\Services\Maria\AgverseOpportunityReviewService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AgverseOpportunityResource extends Resource
{
    protected static ?string $model = AgverseOpportunity::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Agverse Opportunities';

    protected static ?int $navigationSort = 25;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        $score = fn (string $field, string $label) => Forms\Components\Select::make($field)->label($label)->options([1 => '1 · Low', 2 => '2', 3 => '3 · Medium', 4 => '4', 5 => '5 · High'])->required();

        return $form->schema([
            Forms\Components\TextInput::make('name')->required(), Forms\Components\TextInput::make('organization'),
            Forms\Components\Textarea::make('summary')->required()->columnSpanFull(),
            Forms\Components\TextInput::make('expected_value')->numeric(), Forms\Components\Select::make('currency')->options(['AED' => 'AED', 'USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP'])->required(),
            $score('value_score', 'Expected value'), $score('strategic_fit_score', 'Strategic fit'), $score('urgency_score', 'Urgency'), $score('evidence_score', 'Evidence strength'), $score('effort_score', 'Effort'), $score('risk_score', 'Risk'),
            Forms\Components\TagsInput::make('verified_facts')->helperText('Only evidence-backed facts.')->columnSpanFull(),
            Forms\Components\TagsInput::make('hypotheses')->helperText('Unverified assumptions must remain here.')->columnSpanFull(),
            Forms\Components\KeyValue::make('evidence_links')->keyLabel('Evidence')->valueLabel('URL')->columnSpanFull(),
            Forms\Components\Textarea::make('next_step')->columnSpanFull(), Forms\Components\TextInput::make('next_step_owner'),
            Forms\Components\DateTimePicker::make('next_step_at')->displayFormat(config('app.display_datetime_format', 'd/m/Y H:i')), Forms\Components\Toggle::make('approval_required'),
            Forms\Components\Select::make('stage')->options(array_combine($stages = ['research', 'qualifying', 'proposal', 'negotiation', 'won', 'lost'], $stages))->required(),
            Forms\Components\Select::make('status')->options(['active' => 'Active', 'on_hold' => 'On hold', 'completed' => 'Completed', 'archived' => 'Archived'])->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('organization')->searchable(),
            Tables\Columns\TextColumn::make('expected_value')->money(fn ($record) => $record->currency)->sortable(), Tables\Columns\TextColumn::make('priority_score')->sortable()->badge(),
            Tables\Columns\TextColumn::make('stage')->badge(), Tables\Columns\TextColumn::make('next_step_at')->dateTime(config('app.display_datetime_format', 'd/m/Y H:i'))->sortable(), Tables\Columns\IconColumn::make('approval_required')->boolean(),
        ])->defaultSort('priority_score', 'desc')->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function prepareData(array $data): array
    {
        $data['priority_score'] = app(AgverseOpportunityReviewService::class)->score(new AgverseOpportunity($data));

        return $data;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAgverseOpportunities::route('/'), 'create' => Pages\CreateAgverseOpportunity::route('/create'), 'edit' => Pages\EditAgverseOpportunity::route('/{record}/edit')];
    }
}
