<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatalogueEntryResource\Pages\CreateCatalogueEntry;
use App\Filament\Resources\CatalogueEntryResource\Pages\EditCatalogueEntry;
use App\Filament\Resources\CatalogueEntryResource\Pages\ListCatalogueEntries;
use App\Models\CatalogueEntry;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CatalogueEntryResource extends Resource
{
    protected static ?string $model = CatalogueEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'Catalogue Entries';
    }

    public static function form(Form $form): Form
    {
        $locales = config('services_bot.languages', ['en' => 'English']);

        return $form->schema([
            Section::make('Entry')->schema([
                Grid::make(2)->schema([
                    Select::make('whatsapp_account_id')
                        ->label(__('admin.whatsapp_account.label'))
                        ->relationship('whatsAppAccount', 'name')
                        ->required()
                        ->live(),

                    Select::make('service_id')
                        ->label(__('admin.service.label'))
                        ->relationship('service', 'slug')
                        ->helperText('Which service\'s "menu" keyword shows this entry. Leave empty to show under every catalogue-enabled service on this account.'),
                ]),

                Select::make('catalogue_category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getNameForLocale('en')),

                Grid::make(3)->schema([
                    TextInput::make('price')
                        ->numeric()
                        ->step(0.01)
                        ->label('Price')
                        ->helperText('Leave empty for entries without a price (e.g. a doctor or course).')
                        ->afterStateHydrated(function ($component, $record) {
                            $component->state($record && $record->price_minor_units !== null
                                ? $record->price_minor_units / 100
                                : null);
                        }),

                    TextInput::make('currency')
                        ->maxLength(3)
                        ->default('EUR')
                        ->helperText('ISO code, e.g. EUR'),

                    TextInput::make('sort_order')->numeric()->default(0),
                ]),

                Toggle::make('is_active')->default(true),

                ...collect($locales)->map(fn ($name, $code) => TextInput::make("name.{$code}")
                    ->label("Name ({$name})")
                    ->required($code === 'en')
                    ->maxLength(100)
                )->values()->toArray(),

                ...collect($locales)->map(fn ($name, $code) => Textarea::make("description.{$code}")
                    ->label("Description ({$name})")
                    ->rows(2)
                    ->maxLength(300)
                )->values()->toArray(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable()->width(50),
                TextColumn::make('whatsAppAccount.name')->label(__('admin.whatsapp_account.label'))->badge()->color('gray'),
                TextColumn::make('service.slug')->label(__('admin.service.label'))->badge()->color('gray'),
                TextColumn::make('name')
                    ->label('Name')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['en'] ?? '—') : $state),
                TextColumn::make('formatted_price')
                    ->label('Price')
                    ->getStateUsing(fn (CatalogueEntry $record) => $record->formattedPrice() ?? '—'),
                ToggleColumn::make('is_active')->label('Active'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                SelectFilter::make('whatsapp_account_id')
                    ->label(__('admin.whatsapp_account.label'))
                    ->relationship('whatsAppAccount', 'name'),
                SelectFilter::make('service_id')
                    ->label(__('admin.service.label'))
                    ->relationship('service', 'slug'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCatalogueEntries::route('/'),
            'create' => CreateCatalogueEntry::route('/create'),
            'edit' => EditCatalogueEntry::route('/{record}/edit'),
        ];
    }
}
