<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConversationResource\Pages;
use App\Models\Conversation;
use App\Models\Service;
use App\Models\WhatsAppAccount;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make(__('admin.conversation.sections.details'))
                ->schema([
                    TextEntry::make('wa_phone')
                        ->label('Contact identifier')
                        ->hint(fn (Conversation $record) => $record->contactIdentifierType())
                        ->copyable(),

                    TextEntry::make('whatsAppAccount.name')
                        ->label(__('admin.whatsapp_account.label'))
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('platform')
                        ->label('Platform')
                        ->badge()
                        ->color(fn (string $state) => match ($state) {
                            'whatsapp' => 'success',
                            'messenger' => 'info',
                            'instagram' => 'warning',
                            default => 'gray',
                        }),

                    TextEntry::make('step')
                        ->label('Step')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => __("admin.conversation.steps.{$state}"))
                        ->color(fn (string $state) => match ($state) {
                            'NEW' => 'gray',
                            'AWAIT_LANG' => 'warning',
                            'IN_SERVICE' => 'primary',
                            'DONE' => 'success',
                            default => 'gray',
                        }),

                    TextEntry::make('language')
                        ->label(__('admin.conversation.fields.language')),

                    TextEntry::make('service')
                        ->label(__('admin.service_request.fields.service'))
                        ->formatStateUsing(fn (?string $state, Conversation $record) => $state
                            ? (Service::where('slug', $state)->forAccount($record->whatsapp_account_id)->first()?->label[app()->getLocale()] ?? $state)
                            : '—'),

                    TextEntry::make('created_at')
                        ->label(__('admin.conversation.fields.started_at'))
                        ->dateTime(),

                    TextEntry::make('updated_at')
                        ->label(__('admin.conversation.fields.last_activity'))
                        ->dateTime(),
                ])
                ->columns(3),

            InfolistSection::make(__('admin.conversation.sections.transcript'))
                ->schema([
                    RepeatableEntry::make('history')
                        ->label('')
                        ->schema([
                            TextEntry::make('role')
                                ->label(__('admin.conversation.fields.role'))
                                ->badge()
                                ->formatStateUsing(fn (string $state) => __("admin.conversation.roles.{$state}"))
                                ->color(fn (string $state) => $state === 'user' ? 'gray' : 'primary'),

                            TextEntry::make('content')
                                ->label(__('admin.conversation.fields.message'))
                                ->columnSpanFull(),
                        ])
                        ->columns(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wa_phone')
                    ->label('Contact identifier')
                    ->description(fn (Conversation $record) => $record->contactIdentifierType())
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('whatsAppAccount.name')
                    ->label(__('admin.whatsapp_account.label'))
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('platform')
                    ->label('Platform')
                    ->colors([
                        'success' => 'whatsapp',
                        'info' => 'messenger',
                        'warning' => 'instagram',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('step')
                    ->label('Step')
                    ->formatStateUsing(fn (string $state) => __("admin.conversation.steps.{$state}"))
                    ->colors([
                        'gray' => 'NEW',
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
            ->recordUrl(fn (Conversation $record) => static::getUrl('view', ['record' => $record]))
            ->defaultSort('updated_at', 'desc')
            // Explicit page-size options (no "all") — conversations can grow into the
            // thousands across multiple channels, so an unbounded page isn't offered.
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->filters([
                SelectFilter::make('step')
                    ->options([
                        'NEW' => __('admin.conversation.steps.NEW'),
                        'AWAIT_LANG' => __('admin.conversation.steps.AWAIT_LANG'),
                        'AWAIT_SERVICE' => __('admin.conversation.steps.AWAIT_SERVICE'),
                        'IN_SERVICE' => __('admin.conversation.steps.IN_SERVICE'),
                        'DONE' => __('admin.conversation.steps.DONE'),
                    ]),

                SelectFilter::make('whatsapp_account_id')
                    ->label(__('admin.whatsapp_account.label'))
                    ->options(fn () => WhatsAppAccount::pluck('name', 'id')),

                SelectFilter::make('platform')
                    ->label('Platform')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'messenger' => 'Facebook Messenger',
                        'instagram' => 'Instagram',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

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
            'view' => Pages\ViewConversation::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
