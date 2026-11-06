<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KnowledgeDocumentResource\Pages;
use App\Models\KnowledgeDocument;
use App\Models\Service;
use App\Models\WhatsAppAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class KnowledgeDocumentResource extends Resource
{
    protected static ?string $model = KnowledgeDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.knowledge_base');
    }

    public static function getModelLabel(): string
    {
        return __('admin.knowledge_document.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.knowledge_document.label_plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('admin.knowledge_document.sections.details'))->schema([
                // Plain JSON array of whatsapp_accounts.id, NOT a relationship() field:
                // KnowledgeDocument lives on the isolated 'pgvector' connection and can't
                // join against whatsapp_accounts (a different database server). Options
                // are just a lookup query against the main connection.
                Forms\Components\Select::make('account_ids')
                    ->label(__('admin.whatsapp_account.label_plural'))
                    ->options(fn () => WhatsAppAccount::query()->pluck('name', 'id'))
                    ->multiple()
                    ->preload()
                    ->default(fn () => array_filter([WhatsAppAccount::where('is_default', true)->value('id')]))
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('service', null))
                    ->helperText(__('admin.knowledge_document.fields.accounts_help')),

                Forms\Components\Select::make('service')
                    ->label(__('admin.knowledge_document.fields.applies_to'))
                    ->placeholder(__('admin.knowledge_document.fields.all_services'))
                    ->options(
                        fn (Get $get) => collect($get('account_ids') ?? [])
                            ->flatMap(fn ($accountId) => Service::options(app()->getLocale(), (int) $accountId))
                            ->all()
                    ),

                Forms\Components\Select::make('language')
                    ->label(__('admin.knowledge_document.fields.language'))
                    ->options(config('services_bot.languages'))
                    ->default('en')
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->label(__('admin.knowledge_document.fields.active'))
                    ->default(true),

                Forms\Components\TextInput::make('title')
                    ->label(__('admin.knowledge_document.fields.title'))
                    ->required()
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make(__('admin.knowledge_document.sections.content'))->schema([
                Forms\Components\FileUpload::make('source_path')
                    ->label(__('admin.knowledge_document.fields.upload'))
                    ->helperText(__('admin.knowledge_document.fields.upload_help'))
                    ->disk('local')
                    ->directory('knowledge-sources')
                    ->visibility('private')
                    ->acceptedFileTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'])
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('content')
                    ->label(__('admin.knowledge_document.fields.content'))
                    ->helperText(__('admin.knowledge_document.fields.content_help'))
                    ->rows(12)
                    ->required(fn (Get $get) => blank($get('source_path')))
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('account_ids')
                    ->label(__('admin.whatsapp_account.label_plural'))
                    // Filament hands the table column its raw DB value here, not the
                    // cast model attribute — decode defensively rather than relying on
                    // Eloquent's 'array' cast having already run.
                    ->formatStateUsing(function ($state) {
                        $ids = is_string($state) ? json_decode($state, true) : $state;
                        // A row saved outside the normal form (e.g. via tinker) could have
                        // stored a bare scalar instead of an array — normalize rather than
                        // let whereIn() crash on a non-array.
                        $ids = is_array($ids) ? $ids : ($ids !== null ? [$ids] : null);

                        return $ids
                            ? WhatsAppAccount::whereIn('id', $ids)->pluck('name')->implode(', ')
                            : __('admin.knowledge_document.fields.all_accounts');
                    })
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('service')
                    ->label(__('admin.knowledge_document.fields.applies_to'))
                    ->formatStateUsing(fn (?string $state, KnowledgeDocument $record) => $state
                        ? (Service::where('slug', $state)->forAccount($record->account_ids[0] ?? null)->first()?->label[app()->getLocale()] ?? $state)
                        : __('admin.knowledge_document.fields.all_services'))
                    ->badge(),

                Tables\Columns\TextColumn::make('language')
                    ->label(__('admin.knowledge_document.fields.language'))
                    ->badge(),

                Tables\Columns\TextColumn::make('chunks_count')
                    ->label(__('admin.knowledge_document.fields.chunks'))
                    ->counts('chunks'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('admin.knowledge_document.fields.active')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime(config('app.display_datetime_format', 'd/m/Y H:i'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->filters([
                // Not ->relationship(): account_ids is a plain JSON column, not a
                // relationship (see the form field's note on why).
                SelectFilter::make('account_ids')
                    ->label(__('admin.whatsapp_account.label'))
                    ->options(fn () => WhatsAppAccount::query()->pluck('name', 'id'))
                    ->query(fn ($query, array $data) => $data['value']
                        ? $query->whereJsonContains('account_ids', (int) $data['value'])
                        : $query),
                SelectFilter::make('service')
                    ->label(__('admin.knowledge_document.fields.applies_to'))
                    ->options(
                        Service::query()
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn (Service $s) => [
                                $s->slug => ($s->label[app()->getLocale()] ?? $s->label['en'] ?? $s->slug)
                                    .' ('.($s->whatsAppAccount?->name ?? '—').')',
                            ])
                    ),
                TernaryFilter::make('is_active')
                    ->label(__('admin.knowledge_document.fields.active')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKnowledgeDocuments::route('/'),
            'create' => Pages\CreateKnowledgeDocument::route('/create'),
            'edit' => Pages\EditKnowledgeDocument::route('/{record}/edit'),
        ];
    }
}
