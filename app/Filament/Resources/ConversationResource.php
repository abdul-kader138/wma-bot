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
use Filament\Tables\Filters\TernaryFilter;
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

    // Unclaimed "talk to a human" escalations waiting in the queue — the badge that
    // turns this nav item into a real support queue instead of just a conversation list.
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('step', 'HUMAN')->whereNull('assigned_user_id')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
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
                            'HUMAN' => 'danger',
                            'DONE' => 'success',
                            default => 'gray',
                        }),

                    TextEntry::make('escalation_status')
                        ->label('Escalation')
                        ->visible(fn (Conversation $record) => filled($record->escalation_status))
                        ->badge()
                        ->color(fn (?string $state) => match ($state) {
                            'pending' => 'danger',
                            'claimed' => 'warning',
                            default => 'gray',
                        }),

                    TextEntry::make('assignedUser.name')
                        ->label('Claimed by')
                        ->visible(fn (Conversation $record) => filled($record->assigned_user_id))
                        ->badge()
                        ->color('warning'),

                    TextEntry::make('opted_out_at')
                        ->label('Opted out')
                        ->visible(fn (Conversation $record) => filled($record->opted_out_at))
                        ->badge()
                        ->color('gray')
                        ->formatStateUsing(fn () => 'Yes'),

                    TextEntry::make('language')
                        ->label(__('admin.conversation.fields.language')),

                    TextEntry::make('service')
                        ->label(__('admin.service_request.fields.service'))
                        ->formatStateUsing(fn (?string $state, Conversation $record) => $state
                            ? (Service::where('slug', $state)->forAccount($record->whatsapp_account_id)->first()?->label[app()->getLocale()] ?? $state)
                            : '—'),

                    TextEntry::make('created_at')
                        ->label(__('admin.conversation.fields.started_at'))
                        ->dateTime(config('app.display_datetime_format', 'd/m/Y H:i')),

                    TextEntry::make('updated_at')
                        ->label(__('admin.conversation.fields.last_activity'))
                        ->dateTime(config('app.display_datetime_format', 'd/m/Y H:i')),
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
                        'danger' => 'HUMAN',
                        'success' => 'DONE',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('language')
                    ->sortable(),

                Tables\Columns\TextColumn::make('service')
                    ->label(__('admin.service_request.fields.service'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Claimed by')
                    ->placeholder('—')
                    ->badge()
                    ->color('warning')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('opted_out_at')
                    ->label('Opted out')
                    ->formatStateUsing(fn (?string $state) => $state ? 'Yes' : '—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin.conversation.fields.last_activity'))
                    ->dateTime(config('app.display_datetime_format', 'd/m/Y H:i'))
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
                        'HUMAN' => __('admin.conversation.steps.HUMAN'),
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

                Tables\Filters\Filter::make('unclaimed_escalations')
                    ->label('Waiting for staff (unclaimed)')
                    ->query(fn ($query) => $query->where('step', 'HUMAN')->whereNull('assigned_user_id')),

                TernaryFilter::make('opted_out')
                    ->label('Opted out')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('opted_out_at'),
                        false: fn ($query) => $query->whereNull('opted_out_at'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('claim')
                    ->label(__('admin.conversation.actions.claim'))
                    ->icon('heroicon-o-hand-raised')
                    ->color('warning')
                    ->visible(fn (Conversation $c) => $c->step === 'HUMAN' && ! $c->assigned_user_id)
                    ->action(fn (Conversation $c) => $c->update([
                        'assigned_user_id' => auth()->id(),
                        'escalation_status' => 'claimed',
                    ])),

                Tables\Actions\Action::make('release')
                    ->label(__('admin.conversation.actions.release'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (Conversation $c) => $c->step === 'HUMAN' && (bool) $c->assigned_user_id)
                    ->requiresConfirmation()
                    ->action(fn (Conversation $c) => $c->update([
                        'assigned_user_id' => null,
                        'escalation_status' => 'pending',
                    ])),

                Tables\Actions\Action::make('resume')
                    ->label(__('admin.conversation.actions.resume'))
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (Conversation $c) => $c->step === 'HUMAN')
                    ->requiresConfirmation()
                    ->action(fn (Conversation $c) => $c->update([
                        'step' => $c->service ? 'IN_SERVICE' : 'AWAIT_SERVICE',
                        'escalation_status' => 'resolved',
                        'assigned_user_id' => null,
                    ])),

                Tables\Actions\Action::make('reset')
                    ->label(__('admin.conversation.actions.reset'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Conversation $c) => $c->update([
                        'step' => 'NEW', 'service' => null, 'history' => [],
                        'escalation_status' => null, 'escalated_at' => null, 'assigned_user_id' => null,
                    ])),
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
