<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkflowRunResource\Pages;
use App\Models\WorkflowRun;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkflowRunResource extends Resource
{
    protected static ?string $model = WorkflowRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?int $navigationSort = 21;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
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
            Tables\Columns\TextColumn::make('started_at')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('finished_at')->dateTime(),
            Tables\Columns\TextColumn::make('estimated_cost')->money('USD'),
        ])->defaultSort('started_at', 'desc')->actions([Tables\Actions\ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListWorkflowRuns::route('/'), 'view' => Pages\ViewWorkflowRun::route('/{record}')];
    }
}
