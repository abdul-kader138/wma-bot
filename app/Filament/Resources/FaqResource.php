<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
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

    protected static ?string $navigationLabel = 'FAQs';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('FAQ')->schema([
                Forms\Components\Select::make('service')
                    ->label('Applies to')
                    ->placeholder('All services')
                    ->options(
                        collect(config('services_bot.services'))
                            ->mapWithKeys(fn ($s, $k) => [$k => $s['label']['en']])
                            ->toArray()
                    ),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                Forms\Components\TextInput::make('question')
                    ->label('Reference question (for staff)')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TagsInput::make('keywords')
                    ->label('Trigger phrases')
                    ->helperText('Words or short phrases that should trigger this answer, e.g. "price", "how much", "opening hours".')
                    ->required()
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Answer')->schema(
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
                    ->label('Applies to')
                    ->formatStateUsing(fn (?string $state) => $state
                        ? (config("services_bot.services.{$state}.label.en") ?? $state)
                        : 'All services')
                    ->badge(),

                Tables\Columns\TextColumn::make('keywords')
                    ->label('Triggers')
                    ->formatStateUsing(fn (array $state) => implode(', ', $state))
                    ->limit(50),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('service')
                    ->label('Applies to')
                    ->options(
                        collect(config('services_bot.services'))
                            ->mapWithKeys(fn ($s, $k) => [$k => $s['label']['en']])
                            ->toArray()
                    ),
                TernaryFilter::make('is_active')
                    ->label('Active'),
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
