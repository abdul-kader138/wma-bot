<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages\CreateService;
use App\Filament\Resources\ServiceResource\Pages\EditService;
use App\Filament\Resources\ServiceResource\Pages\ListServices;
use App\Models\Service;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.services');
    }

    public static function getModelLabel(): string
    {
        return __('admin.service.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.service.label_plural');
    }

    public static function form(Form $form): Form
    {
        $locales = config('services_bot.languages', ['en' => 'English']);

        return $form->schema([
            Tabs::make('service_tabs')->tabs([

                // ── Basic Info ────────────────────────────────────────────────
                Tab::make(__('admin.service.tabs.basic'))->icon('heroicon-o-information-circle')->schema([

                    Section::make(__('admin.service.sections.identity'))->schema([
                        Grid::make(2)->schema([
                            TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->unique(Service::class, 'slug', ignoreRecord: true)
                                ->alphaNum()
                                ->maxLength(100)
                                ->helperText('Unique identifier used by the bot (lowercase, no spaces). Auto-filled from English label.')
                                ->live(onBlur: true),

                            Select::make('color')
                                ->label(__('admin.service.fields.color'))
                                ->options([
                                    'primary' => 'Primary (Blue)',
                                    'warning' => 'Warning (Amber)',
                                    'success' => 'Success (Green)',
                                    'danger'  => 'Danger (Red)',
                                    'info'    => 'Info (Cyan)',
                                    'gray'    => 'Gray',
                                ])
                                ->required()
                                ->default('primary'),
                        ]),

                        Grid::make(2)->schema([
                            Toggle::make('is_active')
                                ->label(__('admin.service.fields.is_active'))
                                ->default(true)
                                ->helperText('Inactive services will not be shown to WhatsApp users.'),

                            TextInput::make('sort_order')
                                ->label(__('admin.service.fields.sort_order'))
                                ->numeric()
                                ->default(0)
                                ->helperText('Lower number = shown first in the bot menu.'),
                        ]),
                    ]),

                    Section::make(__('admin.service.sections.labels'))
                        ->description('The service name shown to users in each language.')
                        ->schema(
                            collect($locales)->map(fn ($name, $code) =>
                                TextInput::make("label.{$code}")
                                    ->label($name)
                                    ->required($code === 'en')
                                    ->maxLength(100)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state) use ($code, $form) {
                                        if ($code === 'en' && filled($state)) {
                                            $set('slug', Str::slug($state, '_'));
                                        }
                                    })
                            )->values()->toArray()
                        )->columns(count($locales)),
                ]),

                // ── Bot Settings ──────────────────────────────────────────────
                Tab::make(__('admin.service.tabs.bot'))->icon('heroicon-o-cpu-chip')->schema([

                    Section::make(__('admin.service.sections.bot_config'))
                        ->description('How the bot introduces and handles this service.')
                        ->schema([
                            TextInput::make('prompt_label')
                                ->label(__('admin.service.fields.prompt_label'))
                                ->maxLength(255)
                                ->helperText('Short phrase describing the service for Claude, e.g. "booking a travel ticket".')
                                ->columnSpanFull(),

                            TextInput::make('tool_name')
                                ->label(__('admin.service.fields.tool_name'))
                                ->maxLength(100)
                                ->helperText('Python-style function name Claude will call, e.g. submit_ticket_request.')
                                ->regex('/^[a-z][a-z0-9_]*$/')
                                ->placeholder('submit_service_request'),

                            Textarea::make('tool_description')
                                ->label(__('admin.service.fields.tool_description'))
                                ->rows(3)
                                ->maxLength(500)
                                ->helperText('Tells Claude when to call this tool and what it does.')
                                ->columnSpanFull(),
                        ])->columns(2),
                ]),

                // ── Data Fields ───────────────────────────────────────────────
                Tab::make(__('admin.service.tabs.fields'))->icon('heroicon-o-table-cells')->schema([

                    Section::make(__('admin.service.sections.fields'))
                        ->description('Define the information the bot must collect from the user for this service.')
                        ->schema([
                            Repeater::make('tool_fields')
                                ->label('')
                                ->schema([
                                    Grid::make(4)->schema([
                                        TextInput::make('name')
                                            ->label('Field name')
                                            ->required()
                                            ->regex('/^[a-z][a-z0-9_]*$/')
                                            ->helperText('e.g. full_name')
                                            ->maxLength(50),

                                        Select::make('type')
                                            ->label('Type')
                                            ->options([
                                                'string'  => 'String (text)',
                                                'integer' => 'Integer (number)',
                                                'boolean' => 'Boolean (yes/no)',
                                            ])
                                            ->required()
                                            ->default('string'),

                                        Toggle::make('required')
                                            ->label('Required')
                                            ->default(true),

                                        TextInput::make('description')
                                            ->label('Description (for Claude)')
                                            ->required()
                                            ->maxLength(200)
                                            ->helperText('What does this field capture?')
                                            ->columnSpan(1),
                                    ]),
                                ])
                                ->reorderable()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string =>
                                    filled($state['name'] ?? null)
                                        ? ($state['name'] . ' (' . ($state['type'] ?? 'string') . ')' . (($state['required'] ?? false) ? ' *' : ''))
                                        : 'New field'
                                )
                                ->addActionLabel('Add field')
                                ->defaultItems(0),
                        ]),
                ]),

            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('label')
                    ->label(__('admin.service.fields.label_en'))
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['en'] ?? '—') : $state)
                    ->searchable(query: fn ($query, $search) =>
                        $query->whereRaw("JSON_EXTRACT(label, '$.en') LIKE ?", ["%{$search}%"])
                    )
                    ->description(fn (Service $r) =>
                        collect(['it', 'bn'])
                            ->map(fn ($l) => $r->label[$l] ?? null)
                            ->filter()
                            ->join(' · ')
                    ),

                BadgeColumn::make('color')
                    ->label(__('admin.service.fields.color'))
                    ->colors([
                        'primary' => 'primary',
                        'warning' => 'warning',
                        'success' => 'success',
                        'danger'  => 'danger',
                        'info'    => 'info',
                        'gray'    => 'gray',
                    ]),

                TextColumn::make('tool_fields')
                    ->label('Fields')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' fields' : '—')
                    ->badge()
                    ->color('gray'),

                ToggleColumn::make('is_active')
                    ->label(__('admin.service.fields.is_active')),

                TextColumn::make('updated_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
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
            'index'  => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit'   => EditService::route('/{record}/edit'),
        ];
    }
}
