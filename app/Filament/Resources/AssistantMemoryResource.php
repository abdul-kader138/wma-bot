<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssistantMemoryResource\Pages;
use App\Models\AssistantMemory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class AssistantMemoryResource extends MariaResource
{
    protected static ?string $model = AssistantMemory::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Remembered Facts';

    protected static ?int $navigationSort = 23;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('key')->required()->maxLength(120),
            Forms\Components\Textarea::make('value')->required()->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('key')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('value')->wrap()->searchable(),
            Tables\Columns\TextColumn::make('source')->badge(),
            Tables\Columns\TextColumn::make('updated_at')->dateTime(config('app.display_datetime_format', 'd/m/Y H:i'))->sortable(),
        ])->defaultSort('key')->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssistantMemories::route('/'),
            'create' => Pages\CreateAssistantMemory::route('/create'),
            'edit' => Pages\EditAssistantMemory::route('/{record}/edit'),
        ];
    }
}
