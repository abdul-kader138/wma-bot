<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
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

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.faqs');
    }

    public static function getModelLabel(): string
    {
        return __('admin.faq.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.faq.label_plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('admin.faq.sections.faq'))->schema([
                Forms\Components\Select::make('whatsapp_account_id')
                    ->label('WhatsApp Account')
                    ->options(WhatsAppAccount::pluck('name', 'id'))
                    ->default(WhatsAppAccount::where('is_default', true)->value('id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('service', null)),

                Forms\Components\Select::make('service')
                    ->label(__('admin.faq.fields.applies_to'))
                    ->placeholder(__('admin.faq.fields.all_services'))
                    ->options(
                        fn (Get $get) => Service::options(app()->getLocale(), $get('whatsapp_account_id'))
                    ),

                Forms\Components\Toggle::make('is_active')
                    ->label(__('admin.faq.fields.active'))
                    ->default(true),

                Forms\Components\TagsInput::make('keywords')
                    ->label(__('admin.faq.fields.keywords'))
                    ->helperText(__('admin.faq.fields.keywords_help'))
                    ->required()
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make(__('admin.faq.fields.question'))->schema(
                collect(config('services_bot.languages'))
                    ->map(fn ($name, $code) => Forms\Components\TextInput::make("question.{$code}")
                        ->label($name)
                        ->required($code === 'en')
                        ->columnSpanFull())
                    ->values()
                    ->toArray()
            ),

            Forms\Components\Section::make(__('admin.faq.sections.answer'))->schema(
                collect(config('services_bot.languages'))
                    ->map(fn ($name, $code) => Forms\Components\Textarea::make("answer.{$code}")
                        ->label($name)
                        ->rows(3)
                        ->required($code === 'en')
                        ->columnSpanFull())
                    ->values()
                    ->toArray()
            ),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question')
                    ->formatStateUsing(fn (Faq $record) => $record->questionFor(app()->getLocale()))
                    ->searchable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('whatsAppAccount.name')
                    ->label('WhatsApp Account')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('service')
                    ->label(__('admin.faq.fields.applies_to'))
                    ->formatStateUsing(fn (?string $state, Faq $record) => $state
                        ? (Service::where('slug', $state)->where('whatsapp_account_id', $record->whatsapp_account_id)->first()?->label[app()->getLocale()] ?? $state)
                        : __('admin.faq.fields.all_services'))
                    ->badge(),

                Tables\Columns\TextColumn::make('keywords')
                    ->label(__('admin.faq.fields.triggers'))
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : (string) $state)
                    ->limit(50),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('admin.faq.fields.active')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('whatsapp_account_id')
                    ->label('WhatsApp Account')
                    ->options(WhatsAppAccount::pluck('name', 'id')),
                SelectFilter::make('service')
                    ->label(__('admin.faq.fields.applies_to'))
                    ->options(
                        // Slugs are only unique per account, so label each option with
                        // its account name to disambiguate before the account filter is applied.
                        Service::query()
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn (Service $s) => [
                                $s->slug => ($s->label[app()->getLocale()] ?? $s->label['en'] ?? $s->slug)
                                    . ' (' . ($s->whatsAppAccount?->name ?? '—') . ')',
                            ])
                    ),
                TernaryFilter::make('is_active')
                    ->label(__('admin.faq.fields.active')),
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
            'index'  => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit'   => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
