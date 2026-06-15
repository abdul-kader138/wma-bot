<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceRequestResource\Pages;
use App\Models\ServiceRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Service Requests';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Request Details')->schema([
                Forms\Components\TextInput::make('wa_phone')
                    ->label('WhatsApp Phone')
                    ->disabled(),

                Forms\Components\TextInput::make('service')
                    ->label('Service')
                    ->disabled(),

                Forms\Components\Select::make('status')
                    ->options([
                        'new'         => 'New',
                        'in_progress' => 'In Progress',
                        'done'        => 'Done',
                    ])
                    ->required(),

                Forms\Components\KeyValue::make('payload')
                    ->label('Collected Details')
                    ->disabled()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('staff_notes')
                    ->label('Staff Notes')
                    ->rows(4)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wa_phone')
                    ->label('Phone')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('service')
                    ->colors([
                        'primary' => 'ticket',
                        'warning' => 'license',
                        'success' => 'immigration',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger'  => 'new',
                        'warning' => 'in_progress',
                        'success' => 'done',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'new'         => 'New',
                        'in_progress' => 'In Progress',
                        'done'        => 'Done',
                    ]),
                SelectFilter::make('service')
                    ->options(
                        collect(config('services_bot.services'))
                            ->mapWithKeys(fn ($s, $k) => [$k => $s['label']['en']])
                            ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_in_progress')
                    ->label('In Progress')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (ServiceRequest $r) => $r->status === 'new')
                    ->action(fn (ServiceRequest $r) => $r->update(['status' => 'in_progress'])),

                Tables\Actions\Action::make('mark_done')
                    ->label('Done')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ServiceRequest $r) => in_array($r->status, ['new', 'in_progress']))
                    ->action(fn (ServiceRequest $r) => $r->update(['status' => 'done'])),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_done')
                    ->label('Mark as Done')
                    ->icon('heroicon-o-check-circle')
                    ->action(fn ($records) => $records->each->update(['status' => 'done']))
                    ->requiresConfirmation(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServiceRequests::route('/'),
            'edit'   => Pages\EditServiceRequest::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'new')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
