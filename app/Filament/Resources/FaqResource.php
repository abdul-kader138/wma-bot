<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
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
                Forms\Components\Select::make('service')
                    ->label(__('admin.faq.fields.applies_to'))
                    ->placeholder(__('admin.faq.fields.all_services'))
                    ->options(
                        Service::options(app()->getLocale())
                    ),

                Forms\Components\Toggle::make('is_active')
                    ->label(__('admin.faq.fields.active'))
                    ->default(true),

                Forms\Components\TextInput::make('question')
                    ->label(__('admin.faq.fields.question'))
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TagsInput::make('keywords')
                    ->label(__('admin.faq.fields.keywords'))
                    ->helperText(__('admin.faq.fields.keywords_help'))
                    ->required()
                    ->columnSpanFull(),
            ])->columns(2),

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
                    ->searchable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('service')
                    ->label(__('admin.faq.fields.applies_to'))
                    ->formatStateUsing(fn (?string $state) => $state
                        ? (Service::where('slug', $state)->first()?->label[app()->getLocale()] ?? $state)
                        : __('admin.faq.fields.all_services'))
                    ->badge(),

                Tables\Columns\TextColumn::make('keywords')
                    ->label(__('admin.faq.fields.triggers'))
                    ->formatStateUsing(fn (array $state) => implode(', ', $state))
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
                SelectFilter::make('service')
                    ->label(__('admin.faq.fields.applies_to'))
                    ->options(
                        Service::options(app()->getLocale())
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
