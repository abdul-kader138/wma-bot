<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RelationshipRecommendationResource\Pages;
use App\Models\RelationshipRecommendation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RelationshipRecommendationResource extends Resource
{
    protected static ?string $model = RelationshipRecommendation::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Daily Five';

    protected static ?int $navigationSort = 17;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query->with(['contact:id,full_name,organization,tier', 'reviewer:id,name']);

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('contact.full_name')->label('Contact'),
            Forms\Components\TextInput::make('score'), Forms\Components\TextInput::make('recommended_stage'),
            Forms\Components\Textarea::make('relevance')->columnSpanFull(),
            Forms\Components\Textarea::make('warm_path')->columnSpanFull(),
            Forms\Components\Textarea::make('suggested_comment')->columnSpanFull(),
            Forms\Components\Textarea::make('connection_note')->columnSpanFull(),
            Forms\Components\Textarea::make('follow_up')->columnSpanFull(),
            Forms\Components\Textarea::make('review_notes')->columnSpanFull(),
        ])->disabled()->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('recommendation_date')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('contact.full_name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('contact.organization')->searchable(),
            Tables\Columns\TextColumn::make('contact.tier')->label('Tier')->badge(),
            Tables\Columns\TextColumn::make('score')->sortable(),
            Tables\Columns\TextColumn::make('recommended_stage')->badge(),
            Tables\Columns\TextColumn::make('status')->badge(),
        ])->defaultSort('recommendation_date', 'desc')->filters([
            Tables\Filters\SelectFilter::make('status')->options(['pending' => 'Pending review', 'accepted' => 'Accepted', 'rejected' => 'Rejected']),
        ])->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\Action::make('accept')->color('success')->icon('heroicon-o-check')->visible(fn ($record) => $record->status === 'pending')->requiresConfirmation()->action(fn ($record) => $record->update(['status' => 'accepted', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()])),
            Tables\Actions\Action::make('reject')->color('danger')->icon('heroicon-o-x-mark')->visible(fn ($record) => $record->status === 'pending')->requiresConfirmation()->action(fn ($record) => $record->update(['status' => 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()])),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListRelationshipRecommendations::route('/'), 'view' => Pages\ViewRelationshipRecommendation::route('/{record}')];
    }
}
