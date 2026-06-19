<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceRequestResource\Pages;
use App\Models\Service;
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

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.service_requests');
    }

    public static function getModelLabel(): string
    {
        return __('admin.service_request.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.service_request.label_plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('admin.service_request.sections.details'))->schema([
                Forms\Components\TextInput::make('wa_phone')
                    ->label(__('admin.service_request.fields.phone'))
                    ->disabled(),

                Forms\Components\TextInput::make('service')
                    ->label(__('admin.service_request.fields.service'))
                    ->disabled(),

                Forms\Components\Select::make('status')
                    ->label(__('admin.service_request.fields.status'))
                    ->options([
                        'new'         => __('admin.service_request.status.new'),
                        'in_progress' => __('admin.service_request.status.in_progress'),
                        'done'        => __('admin.service_request.status.done'),
                    ])
                    ->required(),

                Forms\Components\KeyValue::make('payload')
                    ->label(__('admin.service_request.fields.payload'))
                    ->disabled()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('staff_notes')
                    ->label(__('admin.service_request.fields.staff_notes'))
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
                    ->label(__('admin.service_request.fields.phone_short'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('service')
                    ->label(__('admin.service_request.fields.service'))
                    ->colors([
                        'primary' => 'ticket',
                        'warning' => 'license',
                        'success' => 'immigration',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('admin.service_request.fields.status'))
                    ->formatStateUsing(fn (string $state) => __("admin.service_request.status.{$state}"))
                    ->colors([
                        'danger'  => 'new',
                        'warning' => 'in_progress',
                        'success' => 'done',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.service_request.fields.received'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin.service_request.fields.last_updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.service_request.fields.status'))
                    ->options([
                        'new'         => __('admin.service_request.status.new'),
                        'in_progress' => __('admin.service_request.status.in_progress'),
                        'done'        => __('admin.service_request.status.done'),
                    ]),
                SelectFilter::make('service')
                    ->label(__('admin.service_request.fields.service'))
                    ->options(fn () => Service::options(app()->getLocale())),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_in_progress')
                    ->label(__('admin.service_request.actions.in_progress'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (ServiceRequest $r) => $r->status === 'new')
                    ->action(fn (ServiceRequest $r) => $r->update(['status' => 'in_progress'])),

                Tables\Actions\Action::make('mark_done')
                    ->label(__('admin.service_request.actions.done'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ServiceRequest $r) => in_array($r->status, ['new', 'in_progress']))
                    ->action(fn (ServiceRequest $r) => $r->update(['status' => 'done'])),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_done')
                    ->label(__('admin.service_request.actions.mark_as_done'))
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
            'index' => Pages\ListServiceRequests::route('/'),
            'edit'  => Pages\EditServiceRequest::route('/{record}/edit'),
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
