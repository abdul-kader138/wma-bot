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

    protected static ?string $navigationLabel = 'Conversations';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wa_phone')
                    ->label('Phone')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('step')
                    ->colors([
                        'gray'    => 'NEW',
                        'warning' => 'AWAIT_LANG',
                        'warning' => 'AWAIT_SERVICE',
                        'primary' => 'IN_SERVICE',
                        'success' => 'DONE',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('language')
                    ->sortable(),

                Tables\Columns\TextColumn::make('service')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('step')
                    ->options([
                        'NEW'           => 'New',
                        'AWAIT_LANG'    => 'Awaiting Language',
                        'AWAIT_SERVICE' => 'Awaiting Service',
                        'IN_SERVICE'    => 'In Service',
                        'DONE'          => 'Done',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('reset')
                    ->label('Reset')
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
