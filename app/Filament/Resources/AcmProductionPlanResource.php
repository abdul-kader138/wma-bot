<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcmProductionPlanResource\Pages;
use App\Models\AcmProductionPlan;
use App\Services\Maria\AcmProductionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AcmProductionPlanResource extends Resource
{
    protected static ?string $model = AcmProductionPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'ACM Production';

    protected static ?int $navigationSort = 26;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('week_start')->required(), Forms\Components\TextInput::make('theme')->required(),
            Forms\Components\Textarea::make('source_notes')->columnSpanFull(),
            Forms\Components\TagsInput::make('core_claims')->helperText('Claims must be current and permitted for All Catholic Media.')->columnSpanFull(),
            Forms\Components\TextInput::make('owner_name')->required(), Forms\Components\DateTimePicker::make('approval_deadline')->required(),
            Forms\Components\Textarea::make('production_package')->disabled()->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null)->rows(24)->columnSpanFull(),
            Forms\Components\Textarea::make('review_notes')->columnSpanFull(), Forms\Components\TextInput::make('status')->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('week_start')->date()->sortable(), Tables\Columns\TextColumn::make('theme')->searchable()->limit(60),
            Tables\Columns\TextColumn::make('owner_name'), Tables\Columns\TextColumn::make('approval_deadline')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('status')->badge(), Tables\Columns\TextColumn::make('generated_at')->dateTime()->placeholder('Not generated'),
        ])->defaultSort('week_start', 'desc')->actions([
            Tables\Actions\Action::make('generate')->label('Generate plan')->icon('heroicon-o-sparkles')->visible(fn ($record) => in_array($record->status, ['planned', 'blocked_claims'], true))->requiresConfirmation()->action(function ($record) {
                app(AcmProductionService::class)->generate($record);
                Notification::make()->title('Draft ACM production plan generated')->success()->send();
            }),
            Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAcmProductionPlans::route('/'), 'create' => Pages\CreateAcmProductionPlan::route('/create'), 'edit' => Pages\EditAcmProductionPlan::route('/{record}/edit')];
    }
}
