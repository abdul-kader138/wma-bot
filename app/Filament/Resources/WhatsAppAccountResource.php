<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppAccountResource\Pages\CreateWhatsAppAccount;
use App\Filament\Resources\WhatsAppAccountResource\Pages\EditWhatsAppAccount;
use App\Filament\Resources\WhatsAppAccountResource\Pages\ListWhatsAppAccounts;
use App\Models\WhatsAppAccount;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WhatsAppAccountResource extends Resource
{
    protected static ?string $model = WhatsAppAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?int $navigationSort = 98;

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.whatsapp_accounts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.groups.administration');
    }

    public static function getModelLabel(): string
    {
        return __('admin.whatsapp_account.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.whatsapp_account.label_plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('admin.whatsapp_account.sections.identity'))->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->label(__('admin.whatsapp_account.fields.name'))
                        ->helperText(__('admin.whatsapp_account.fields.name_help'))
                        ->required()
                        ->maxLength(100),

                    Select::make('platform')
                        ->label('Platform')
                        ->options([
                            'whatsapp' => 'WhatsApp',
                            'messenger' => 'Facebook Messenger',
                            'instagram' => 'Instagram',
                        ])
                        ->default('whatsapp')
                        ->required()
                        ->live()
                        ->native(false)
                        ->helperText('Which platform this account sends and receives through.'),
                ]),

                Grid::make(2)->schema([
                    Toggle::make('is_active')
                        ->label(__('admin.whatsapp_account.fields.is_active'))
                        ->helperText(__('admin.whatsapp_account.fields.is_active_help'))
                        ->default(true),

                    Toggle::make('is_default')
                        ->label(__('admin.whatsapp_account.fields.is_default'))
                        ->helperText('The default account for this platform when a service doesn\'t specify one.'),
                ]),
            ]),

            Section::make(__('admin.whatsapp_account.sections.credentials'))
                ->description('Configure this account\'s Meta API credentials — the fields differ by platform above.')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('phone_number_id')
                            ->label(__('admin.whatsapp_account.fields.phone_number_id'))
                            ->helperText(__('admin.whatsapp_account.fields.phone_number_id_help'))
                            ->visible(fn ($get) => $get('platform') === 'whatsapp')
                            ->required(fn ($get) => $get('platform') === 'whatsapp')
                            ->unique(WhatsAppAccount::class, 'phone_number_id', ignoreRecord: true)
                            ->maxLength(100),

                        TextInput::make('waba_id')
                            ->label(__('admin.whatsapp_account.fields.waba_id'))
                            ->visible(fn ($get) => $get('platform') === 'whatsapp')
                            ->maxLength(100),

                        TextInput::make('external_id')
                            ->label(fn ($get) => $get('platform') === 'instagram' ? 'Instagram Business Account ID' : 'Facebook Page ID')
                            ->helperText('From the Meta App dashboard for the linked Page (Messenger) or connected Instagram Professional account.')
                            ->visible(fn ($get) => in_array($get('platform'), ['messenger', 'instagram']))
                            ->required(fn ($get) => in_array($get('platform'), ['messenger', 'instagram']))
                            ->unique(WhatsAppAccount::class, 'external_id', ignoreRecord: true)
                            ->maxLength(100)
                            ->columnSpan(2),

                        TextInput::make('api_version')
                            ->label(__('admin.whatsapp_account.fields.api_version'))
                            ->required()
                            ->default('v22.0')
                            ->placeholder('v22.0')
                            ->maxLength(10),
                    ]),

                    TextInput::make('access_token')
                        ->label(fn ($get) => $get('platform') === 'whatsapp'
                            ? __('admin.whatsapp_account.fields.access_token')
                            : 'Page Access Token')
                        ->helperText(__('admin.whatsapp_account.fields.access_token_help'))
                        ->password()
                        ->revealable()
                        ->required()
                        ->maxLength(1000)
                        ->autocomplete('new-password'),

                    TextInput::make('app_secret')
                        ->label('App Secret Override')
                        ->helperText('Only needed if this account\'s number/page/IG account belongs to a different Meta App than the one configured in System Settings — e.g. a separate Meta Business account. Leave blank to use the shared secret from .env.')
                        ->password()
                        ->revealable()
                        ->maxLength(1000)
                        ->autocomplete('new-password'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('platform')
                    ->label('Platform')
                    ->colors([
                        'success' => 'whatsapp',
                        'info' => 'messenger',
                        'warning' => 'instagram',
                    ]),

                TextColumn::make('phone_number_id')
                    ->label(__('admin.whatsapp_account.fields.phone_number_id'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),

                TextColumn::make('external_id')
                    ->label('External ID')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->label(__('admin.whatsapp_account.fields.is_active'))
                    ->boolean(),

                IconColumn::make('is_default')
                    ->label(__('admin.whatsapp_account.fields.is_default'))
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->dateTime(config('app.display_date_format', 'd/m/Y'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsAppAccounts::route('/'),
            'create' => CreateWhatsAppAccount::route('/create'),
            'edit' => EditWhatsAppAccount::route('/{record}/edit'),
        ];
    }
}
