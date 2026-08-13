<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentItemResource\Pages;
use App\Models\ContentItem;
use App\Services\Maria\ContentPackageService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContentItemResource extends Resource
{
    protected static ?string $model = ContentItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Content Packages';

    protected static ?int $navigationSort = 23;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('brand')->options([
                'Fr. Morson' => 'Fr. Morson', 'All Catholic Media' => 'All Catholic Media',
                'Agverse AI UAE' => 'Agverse AI UAE', 'Books' => 'Books',
            ])->required(),
            Forms\Components\TextInput::make('content_pillar'),
            Forms\Components\TextInput::make('audience')->columnSpanFull(),
            Forms\Components\Textarea::make('source_idea')->required()->rows(5)->columnSpanFull(),
            Forms\Components\TextInput::make('source_url')->url()->columnSpanFull(),
            Forms\Components\TagsInput::make('core_claims')->helperText('Each factual claim must exactly match a current Claims Registry entry permitted for this brand.')->columnSpanFull(),
            Forms\Components\Textarea::make('master_draft')->disabled()->rows(12)->columnSpanFull(),
            Forms\Components\Textarea::make('derivatives')->disabled()->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null)->rows(20)->columnSpanFull(),
            Forms\Components\Textarea::make('review_notes')->columnSpanFull(),
            Forms\Components\TextInput::make('status')->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('brand')->badge()->searchable(),
            Tables\Columns\TextColumn::make('source_idea')->limit(70)->searchable(),
            Tables\Columns\TextColumn::make('content_pillar')->searchable(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('generated_at')->dateTime(config('app.display_datetime_format', 'd/m/Y H:i'))->sortable()->placeholder('Not generated'),
        ])->defaultSort('created_at', 'desc')->actions([
            Tables\Actions\Action::make('generate')
                ->label('Generate package')->icon('heroicon-o-sparkles')
                ->visible(fn (ContentItem $record) => in_array($record->status, ['idea', 'blocked_claims', 'failed'], true))
                ->requiresConfirmation()
                ->action(function (ContentItem $record): void {
                    app(ContentPackageService::class)->generate($record);
                    Notification::make()->title('Draft content package generated')->success()->send();
                }),
            Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListContentItems::route('/'), 'create' => Pages\CreateContentItem::route('/create'), 'edit' => Pages\EditContentItem::route('/{record}/edit')];
    }
}
