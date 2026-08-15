<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkflowRunResource\Pages;
use App\Models\WorkflowRun;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkflowRunResource extends MariaResource
{
    protected static ?string $model = WorkflowRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?int $navigationSort = 21;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user:id,name');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('run_id'), Forms\Components\TextInput::make('workflow_type'),
            Forms\Components\TextInput::make('status'), Forms\Components\TextInput::make('prompt_version'),
            Forms\Components\KeyValue::make('input_references')->columnSpanFull(),
            Forms\Components\KeyValue::make('source_gaps')->columnSpanFull(),
            Forms\Components\Textarea::make('error')->columnSpanFull(),
        ])->disabled();
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('run_id')->searchable()->copyable(),
            Tables\Columns\TextColumn::make('workflow_type')->badge()->searchable(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('started_at')->dateTime(config('app.display_datetime_format', 'd/m/Y H:i'))->sortable(),
            Tables\Columns\TextColumn::make('finished_at')->dateTime(config('app.display_datetime_format', 'd/m/Y H:i')),
            Tables\Columns\TextColumn::make('estimated_cost')->money('USD'),
        ])->defaultSort('started_at', 'desc')->actions([
            Tables\Actions\Action::make('verify_time_saving')->label('Verify time saved')->icon('heroicon-o-clock')->form([
                Forms\Components\TextInput::make('human_minutes')->label('Actual human minutes spent')->numeric()->minValue(0)->required(),
            ])->action(function (WorkflowRun $record, array $data): void {
                $human = (int) $data['human_minutes'];
                $record->update(['human_minutes' => $human, 'verified_time_saved_minutes' => max(0, $record->estimated_manual_minutes - $human), 'time_saving_verified_at' => now(), 'time_saving_verified_by' => auth()->id()]);
            }),
            Tables\Actions\ViewAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListWorkflowRuns::route('/'), 'view' => Pages\ViewWorkflowRun::route('/{record}')];
    }
}
