<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromptVersionResource\Pages;
use App\Models\PromptVersion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PromptVersionResource extends MariaResource
{
    protected static ?string $model = PromptVersion::class;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket-square';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?int $navigationSort = 22;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('prompt_type')->required()->maxLength(100),
            Forms\Components\TextInput::make('version')->required()->maxLength(50),
            Forms\Components\Textarea::make('content')->required()->rows(18)->columnSpanFull(),
            Forms\Components\KeyValue::make('output_schema')->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')->helperText('Activating this version disables other versions of the same prompt type.'),
            Forms\Components\Textarea::make('change_notes')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('prompt_type')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('version')->searchable(),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('creator.name')->label('Author'),
            Tables\Columns\TextColumn::make('updated_at')->dateTime(config('app.display_datetime_format', 'd/m/Y H:i'))->sortable(),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function prepareData(array $data, ?PromptVersion $record = null): array
    {
        $data['created_by'] = $record?->created_by ?? auth()->id();
        $data['content_hash'] = hash('sha256', Str::of($data['content'])->trim()->toString());
        if ($data['is_active'] ?? false) {
            DB::table('prompt_versions')->where('prompt_type', $data['prompt_type'])->when($record, fn ($query) => $query->where('id', '!=', $record->id))->update(['is_active' => false, 'updated_at' => now()]);
        }

        return $data;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListPromptVersions::route('/'), 'create' => Pages\CreatePromptVersion::route('/create'), 'edit' => Pages\EditPromptVersion::route('/{record}/edit')];
    }
}
