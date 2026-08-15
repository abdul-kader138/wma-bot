<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookResource\Pages;
use App\Models\Book;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class BookResource extends MariaResource
{
    protected static ?string $model = Book::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Book Portfolio';

    protected static ?int $navigationSort = 24;

    public static function form(Form $form): Form
    {
        $stages = ['idea', 'outline', 'drafting', 'editing', 'design', 'proofing', 'publishing', 'published'];

        return $form->schema([
            Forms\Components\TextInput::make('exact_title')->required(), Forms\Components\TextInput::make('subtitle'),
            Forms\Components\Textarea::make('credits')->columnSpanFull(),
            Forms\Components\TextInput::make('edition'), Forms\Components\Select::make('stage')->options(array_combine($stages, array_map(fn ($stage) => str($stage)->headline()->toString(), $stages)))->required(),
            Forms\Components\TextInput::make('manuscript_url')->url()->columnSpanFull(),
            Forms\Components\TextInput::make('current_milestone'), Forms\Components\TextInput::make('milestone_owner'),
            Forms\Components\DatePicker::make('milestone_due_at')->displayFormat(config('app.display_date_format', 'd/m/Y')), Forms\Components\DatePicker::make('publication_target')->displayFormat(config('app.display_date_format', 'd/m/Y')),
            Forms\Components\Textarea::make('blocker')->columnSpanFull(),
            Forms\Components\KeyValue::make('contributors')->keyLabel('Contributor')->valueLabel('Status')->columnSpanFull(),
            Forms\Components\TextInput::make('marketing_status'),
            Forms\Components\Select::make('status')->options(['active' => 'Active', 'on_hold' => 'On hold', 'completed' => 'Completed', 'archived' => 'Archived'])->required(),
            Forms\Components\Textarea::make('next_action')->columnSpanFull(), Forms\Components\DatePicker::make('next_action_at')->displayFormat(config('app.display_date_format', 'd/m/Y')),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('exact_title')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('edition')->searchable()->placeholder('Unspecified'),
            Tables\Columns\TextColumn::make('stage')->badge(), Tables\Columns\TextColumn::make('current_milestone')->limit(40),
            Tables\Columns\TextColumn::make('milestone_owner'), Tables\Columns\TextColumn::make('milestone_due_at')->date(config('app.display_date_format', 'd/m/Y'))->sortable(),
            Tables\Columns\IconColumn::make('blocker')->label('Blocked')->boolean(),
            Tables\Columns\TextColumn::make('status')->badge(),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListBooks::route('/'), 'create' => Pages\CreateBook::route('/create'), 'edit' => Pages\EditBook::route('/{record}/edit')];
    }
}
