<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetingResource\Pages;
use App\Models\Meeting;
use App\Services\Maria\MeetingCloseoutService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?int $navigationSort = 15;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->disabled(),
            Forms\Components\DateTimePicker::make('starts_at')->displayFormat('d/m/Y H:i')->disabled(),
            Forms\Components\DateTimePicker::make('ends_at')->displayFormat('d/m/Y H:i')->disabled(),
            Forms\Components\TextInput::make('preparation_status')->disabled(),
            Forms\Components\KeyValue::make('brief')->disabled()->columnSpanFull(),
            Forms\Components\Textarea::make('objective')->columnSpanFull(),
            Forms\Components\Select::make('tier')->options(['A' => 'A', 'B' => 'B', 'C' => 'C']),
            Forms\Components\Select::make('domain')->options(MariaProjectResource::domains()),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable(),
            Tables\Columns\TextColumn::make('starts_at')->dateTime('d/m/Y H:i')->sortable(),
            Tables\Columns\TextColumn::make('tier')->badge()->placeholder('—'),
            Tables\Columns\TextColumn::make('preparation_status')->badge(),
            Tables\Columns\IconColumn::make('brief')->boolean(),
        ])->defaultSort('starts_at')->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('closeout')->label('Process notes')->icon('heroicon-o-document-check')
                ->form([Forms\Components\Textarea::make('notes')->required()->rows(12)])
                ->action(function (Meeting $record, array $data): void {
                    app(MeetingCloseoutService::class)->close($record, $data['notes']);
                    Notification::make()->title('Meeting closeout prepared')->success()->send();
                }),
        ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListMeetings::route('/'), 'edit' => Pages\EditMeeting::route('/{record}/edit')];
    }
}
