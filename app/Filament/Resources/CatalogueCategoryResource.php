<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatalogueCategoryResource\Pages\CreateCatalogueCategory;
use App\Filament\Resources\CatalogueCategoryResource\Pages\EditCatalogueCategory;
use App\Filament\Resources\CatalogueCategoryResource\Pages\ListCatalogueCategories;
use App\Models\CatalogueCategory;
use App\Models\WhatsAppAccount;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
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

class CatalogueCategoryResource extends Resource
{
    protected static ?string $model = CatalogueCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Catalogue Categories';
    }

    public static function form(Form $form): Form
    {
        $locales = config('services_bot.languages', ['en' => 'English']);

        return $form->schema([
            Section::make('Category')->schema([
                Grid::make(2)->schema([
                    Select::make('whatsapp_account_id')
                        ->label(__('admin.whatsapp_account.label'))
                        ->relationship('whatsAppAccount', 'name')
                        ->required(),

                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower number = shown first.'),
                ]),

                Toggle::make('is_active')->default(true),

                ...collect($locales)->map(fn ($name, $code) => TextInput::make("name.{$code}")
                    ->label($name)
                    ->required($code === 'en')
                    ->maxLength(100)
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
                TextColumn::make('name')
                    ->label('Name')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['en'] ?? '—') : $state),
                ToggleColumn::make('is_active')->label('Active'),
                TextColumn::make('entries_count')->label('Entries')->counts('entries')->badge()->color('gray'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                SelectFilter::make('whatsapp_account_id')
                    ->label(__('admin.whatsapp_account.label'))
                    ->relationship('whatsAppAccount', 'name'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCatalogueCategories::route('/'),
            'create' => CreateCatalogueCategory::route('/create'),
            'edit' => EditCatalogueCategory::route('/{record}/edit'),
        ];
    }
}
