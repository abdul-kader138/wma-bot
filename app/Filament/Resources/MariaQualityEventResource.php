<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MariaQualityEventResource\Pages;
use App\Models\MariaQualityEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MariaQualityEventResource extends MariaResource
{
    protected static ?string $model = MariaQualityEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Quality & Corrections';

    protected static ?int $navigationSort = 27;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('event_type')->options(['correction' => 'Human correction', 'safety_incident' => 'Safety incident', 'feedback' => 'Feedback'])->required(),
            Forms\Components\Select::make('category')->options(['identity' => 'Identity', 'confidentiality' => 'Confidentiality', 'deadline' => 'Deadline', 'classification' => 'Classification', 'recipient' => 'Recipient', 'attachment' => 'Attachment', 'unauthorized_action' => 'Unauthorized action', 'claim' => 'Claim', 'voice' => 'Voice', 'priority' => 'Priority', 'duplicate_action' => 'Duplicate action', 'other' => 'Other'])->required(),
            Forms\Components\Select::make('severity')->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'])->required(),
            Forms\Components\Select::make('workflow_run_id')->relationship('workflowRun', 'run_id', fn (Builder $query) => auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id()))->searchable()->preload(),
            Forms\Components\Textarea::make('description')->required()->columnSpanFull(), Forms\Components\Textarea::make('expected_result')->columnSpanFull(),
            Forms\Components\Textarea::make('actual_result')->columnSpanFull(), Forms\Components\Textarea::make('resolution')->columnSpanFull(),
            Forms\Components\Select::make('status')->options(['open' => 'Open', 'investigating' => 'Investigating', 'resolved' => 'Resolved'])->required(),
            Forms\Components\DateTimePicker::make('occurred_at')->displayFormat(config('app.display_datetime_format', 'd/m/Y H:i'))->required(), Forms\Components\DateTimePicker::make('resolved_at')->displayFormat(config('app.display_datetime_format', 'd/m/Y H:i')),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('event_type')->badge(), Tables\Columns\TextColumn::make('category')->badge(),
            Tables\Columns\TextColumn::make('severity')->badge(), Tables\Columns\TextColumn::make('description')->limit(70)->searchable(),
            Tables\Columns\TextColumn::make('status')->badge(), Tables\Columns\TextColumn::make('occurred_at')->dateTime(config('app.display_datetime_format', 'd/m/Y H:i'))->sortable(),
        ])->defaultSort('occurred_at', 'desc')->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListMariaQualityEvents::route('/'), 'create' => Pages\CreateMariaQualityEvent::route('/create'), 'edit' => Pages\EditMariaQualityEvent::route('/{record}/edit')];
    }
}
