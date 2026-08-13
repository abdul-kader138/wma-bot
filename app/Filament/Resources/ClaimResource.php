<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClaimResource\Pages;
use App\Models\Claim;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClaimResource extends Resource
{
    protected static ?string $model = Claim::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Claims Registry';

    protected static ?int $navigationSort = 17;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('claim_text')->required()->columnSpanFull(),
            Forms\Components\TextInput::make('subject')->required(),
            Forms\Components\TextInput::make('category')->required(),
            Forms\Components\TextInput::make('source_url')->url()->columnSpanFull(),
            Forms\Components\DateTimePicker::make('verified_at')->displayFormat('d/m/Y H:i'),
            Forms\Components\DateTimePicker::make('recheck_at')->displayFormat('d/m/Y H:i'),
            Forms\Components\TagsInput::make('permitted_brands')->suggestions(['Fr. Morson', 'All Catholic Media', 'Agverse AI UAE', 'Books'])->columnSpanFull(),
            Forms\Components\Select::make('status')->options(['unverified' => 'Unverified', 'verified' => 'Verified', 'expired' => 'Expired', 'rejected' => 'Rejected'])->required(),
            Forms\Components\Textarea::make('notes')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('claim_text')->limit(70)->searchable(),
            Tables\Columns\TextColumn::make('subject')->searchable(),
            Tables\Columns\TextColumn::make('category')->badge(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('recheck_at')->dateTime('d/m/Y H:i')->sortable()->placeholder('No expiry'),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListClaims::route('/'), 'create' => Pages\CreateClaim::route('/create'), 'edit' => Pages\EditClaim::route('/{record}/edit')];
    }
}
