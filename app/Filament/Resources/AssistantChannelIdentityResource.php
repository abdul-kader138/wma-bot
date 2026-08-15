<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssistantChannelIdentityResource\Pages;
use App\Models\AssistantChannelIdentity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssistantChannelIdentityResource extends MariaResource
{
    protected static ?string $model = AssistantChannelIdentity::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Verified Identities';

    protected static ?int $navigationSort = 19;

    /** Channel identities have no owner column; visibility is governed entirely by the Shield permission. */
    protected static bool $scopeToOwner = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['profile.user:id,name,email', 'channelAccount:id,name']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('assistant_profile_id')->relationship('profile', 'id')->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name.' · '.$record->user?->email)->searchable()->preload()->required(),
            Forms\Components\Select::make('whatsapp_account_id')->relationship('channelAccount', 'name')->searchable()->preload(),
            Forms\Components\Select::make('platform')->options(['whatsapp' => 'WhatsApp'])->required(),
            Forms\Components\TextInput::make('external_identifier')->required()->maxLength(255),
            Forms\Components\TextInput::make('label')->maxLength(255),
            Forms\Components\DateTimePicker::make('verified_at')->displayFormat(config('app.display_datetime_format', 'd/m/Y H:i'))->helperText('Only verified, active identities can enter private Maria mode.'),
            Forms\Components\Toggle::make('is_active')->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('profile.user.name')->label('Owner')->searchable(),
            Tables\Columns\TextColumn::make('platform')->badge(),
            Tables\Columns\TextColumn::make('external_identifier')->searchable(),
            Tables\Columns\TextColumn::make('label'),
            Tables\Columns\TextColumn::make('verified_at')->dateTime(config('app.display_datetime_format', 'd/m/Y H:i'))->placeholder('Unverified'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAssistantChannelIdentities::route('/'), 'create' => Pages\CreateAssistantChannelIdentity::route('/create'), 'edit' => Pages\EditAssistantChannelIdentity::route('/{record}/edit')];
    }
}
