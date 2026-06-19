<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.users');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.groups.administration');
    }

    public static function getModelLabel(): string
    {
        return __('admin.user.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.user.label_plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('admin.user.sections.account'))->schema([
                TextInput::make('name')
                    ->label(__('admin.user.fields.name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label(__('admin.user.fields.email'))
                    ->email()
                    ->required()
                    ->unique(User::class, 'email', ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('password')
                    ->label(__('admin.user.fields.password'))
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->minLength(8)
                    ->maxLength(255)
                    ->helperText(__('admin.user.fields.password_help')),

                TextInput::make('password_confirmation')
                    ->label(__('admin.user.fields.confirm_password'))
                    ->password()
                    ->revealable()
                    ->same('password')
                    ->required(fn (string $operation) => $operation === 'create')
                    ->maxLength(255)
                    ->dehydrated(false),
            ])->columns(2),

            Section::make(__('admin.user.sections.roles'))->schema([
                Select::make('roles')
                    ->label(__('admin.user.fields.roles'))
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->options(Role::pluck('name', 'id'))
                    ->preload()
                    ->helperText(__('admin.user.fields.roles_help')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.user.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('admin.user.fields.email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label(__('admin.user.fields.roles'))
                    ->badge()
                    ->separator(',')
                    ->color('primary'),

                TextColumn::make('email_verified_at')
                    ->label(__('admin.user.fields.verified'))
                    ->dateTime('d M Y')
                    ->sortable()
                    ->placeholder(__('admin.user.fields.not_verified')),

                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label(__('admin.user.fields.role'))
                    ->preload(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }
}
