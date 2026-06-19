<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConversationResource\Pages;
use App\Models\Conversation;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.conversations');
    }

    public static function getModelLabel(): string
    {
        return __('admin.conversation.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.conversation.label_plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wa_phone')
                    ->label(__('admin.conversation.fields.phone'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('step')
                    ->label('Step')
                    ->formatStateUsing(fn (string $state) => __("admin.conversation.steps.{$state}"))
                    ->colors([
                        'gray'    => 'NEW',
                        'warning' => 'AWAIT_LANG',
                        'primary' => 'IN_SERVICE',
                        'success' => 'DONE',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('language')
                    ->sortable(),

                Tables\Columns\TextColumn::make('service')
                    ->label(__('admin.service_request.fields.service'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin.conversation.fields.last_activity'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('step')
                    ->options([
                        'NEW'           => __('admin.conversation.steps.NEW'),
                        'AWAIT_LANG'    => __('admin.conversation.steps.AWAIT_LANG'),
                        'AWAIT_SERVICE' => __('admin.conversation.steps.AWAIT_SERVICE'),
                        'IN_SERVICE'    => __('admin.conversation.steps.IN_SERVICE'),
                        'DONE'          => __('admin.conversation.steps.DONE'),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('reset')
                    ->label(__('admin.conversation.actions.reset'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Conversation $c) => $c->update(['step' => 'NEW', 'service' => null, 'history' => []])),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConversations::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
