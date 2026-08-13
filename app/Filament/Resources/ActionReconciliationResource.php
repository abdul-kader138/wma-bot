<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActionReconciliationResource\Pages;
use App\Models\ActionReconciliation;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActionReconciliationResource extends Resource
{
    protected static ?string $model = ActionReconciliation::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Action Reconciliation';

    protected static ?int $navigationSort = 11;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function table(Table $table): Table
    {
        $resolve = function (ActionReconciliation $record, array $data, string $status): void {
            $record->update(['status' => $status, 'provider_evidence' => $data['provider_evidence'] ?? null, 'resolution_notes' => $data['resolution_notes'], 'resolved_by' => auth()->id(), 'resolved_at' => now()]);
            if ($status === 'confirmed_completed') {
                $record->action->update(['status' => 'completed', 'provider_confirmation_id' => $data['provider_confirmation_id'] ?? 'manually-reconciled', 'executed_at' => now(), 'error' => null]);
            }
            if ($status === 'confirmed_not_executed') {
                $record->action->update(['status' => 'failed']);
            }
        };
        $form = [Forms\Components\TextInput::make('provider_confirmation_id'), Forms\Components\Textarea::make('provider_evidence')->helperText('Optional JSON evidence.'), Forms\Components\Textarea::make('resolution_notes')->required()];

        return $table->columns([
            Tables\Columns\TextColumn::make('action.tool_name')->searchable(), Tables\Columns\TextColumn::make('provider')->badge(),
            Tables\Columns\TextColumn::make('reason')->limit(80), Tables\Columns\TextColumn::make('status')->badge(), Tables\Columns\TextColumn::make('created_at')->dateTime(config('app.display_datetime_format', 'd/m/Y H:i'))->sortable(),
        ])->defaultSort('created_at', 'desc')->actions([
            Tables\Actions\Action::make('confirm_completed')->color('success')->visible(fn ($record) => $record->status === 'pending')->form($form)->requiresConfirmation()->action(fn ($record, $data) => $resolve($record, $data, 'confirmed_completed')),
            Tables\Actions\Action::make('confirm_not_executed')->color('warning')->visible(fn ($record) => $record->status === 'pending')->form($form)->requiresConfirmation()->action(fn ($record, $data) => $resolve($record, $data, 'confirmed_not_executed')),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListActionReconciliations::route('/')];
    }
}
