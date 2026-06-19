<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Providers\Filament\AdminPanelProvider;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SystemSettings extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?int    $navigationSort = 99;

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.system_settings');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.groups.administration');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $superAdminName = (string) config('filament-shield.super_admin.name', 'super_admin');

        if ((bool) config('filament-shield.super_admin.enabled', true) && $user->hasRole($superAdminName)) {
            return true;
        }

        return $user->can('page_SystemSettings');
    }

    public function getTitle(): string
    {
        return __('admin.settings.title');
    }

    public function getView(): string
    {
        return 'filament.pages.system-settings';
    }

    public function mount(): void
    {
        $this->form->fill([
            // General
            'app_name'         => Setting::get('app_name',         config('app.name', 'WMA Bot')),
            'app_tagline'      => Setting::get('app_tagline',      ''),
            'support_email'    => Setting::get('support_email',    ''),
            'maintenance_mode' => Setting::get('maintenance_mode', false),

            // Appearance
            'admin_theme'            => Setting::get('admin_theme',            'amber'),
            'admin_panel_theme_mode' => Setting::get('admin_panel_theme_mode', 'dark'),
            'auth_theme_mode'        => Setting::get('auth_theme_mode',        'dark'),
            'auth_background'        => Setting::get('auth_background',        'inherit'),
            'app_logo'               => Setting::get('app_logo'),
            'app_icon'               => Setting::get('app_icon'),
            'login_image'            => Setting::get('login_image'),
            'favicon'                => Setting::get('favicon'),

            // WhatsApp
            'whatsapp_phone_number_id' => Setting::get('whatsapp_phone_number_id', env('WHATSAPP_PHONE_NUMBER_ID', '')),
            'whatsapp_access_token'    => Setting::get('whatsapp_access_token',    ''),
            'whatsapp_verify_token'    => Setting::get('whatsapp_verify_token',    env('WHATSAPP_VERIFY_TOKEN', '')),
            'whatsapp_api_version'     => Setting::get('whatsapp_api_version',     env('WHATSAPP_API_VERSION', 'v22.0')),

            // Claude AI
            'claude_api_key'     => Setting::get('claude_api_key',     ''),
            'claude_model'       => Setting::get('claude_model',        env('CLAUDE_MODEL', 'claude-haiku-4-5-20251001')),
            'claude_max_tokens'  => Setting::get('claude_max_tokens',   1024),
            'claude_temperature' => Setting::get('claude_temperature',  0.7),

            // Bot Behaviour
            'faq_confidence_threshold' => Setting::get('faq_confidence_threshold', 0.7),
            'bot_welcome_message'      => Setting::get('bot_welcome_message',      'Hello! How can I help you today?'),
            'bot_fallback_message'     => Setting::get('bot_fallback_message',     "I'm sorry, I don't understand. Please contact our support team."),

            // Email
            'mail_from_name'    => Setting::get('mail_from_name',    config('mail.from.name', '')),
            'mail_from_address' => Setting::get('mail_from_address', config('mail.from.address', '')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Tabs::make('settings_tabs')->tabs([

                    // ── General ──────────────────────────────────────────────
                    Tab::make(__('admin.settings.tabs.general'))
                        ->icon('heroicon-o-home')
                        ->schema([
                            Section::make(__('admin.settings.sections.application'))->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('app_name')
                                        ->label(__('admin.settings.fields.app_name'))
                                        ->required()
                                        ->maxLength(100),

                                    TextInput::make('app_tagline')
                                        ->label(__('admin.settings.fields.app_tagline'))
                                        ->maxLength(200)
                                        ->placeholder('WhatsApp AI Assistant'),
                                ]),

                                TextInput::make('support_email')
                                    ->label(__('admin.settings.fields.support_email'))
                                    ->email()
                                    ->maxLength(255),

                                Toggle::make('maintenance_mode')
                                    ->label(__('admin.settings.fields.maintenance_mode'))
                                    ->helperText(__('admin.settings.fields.maintenance_help')),

                                Select::make('admin_locale')
                                    ->label(__('admin.settings.fields.default_language'))
                                    ->helperText(__('admin.settings.fields.default_language_help'))
                                    ->options(config('locales.available', []))
                                    ->default(config('locales.default', 'en'))
                                    ->required()
                                    ->native(false),
                            ]),
                        ]),

                    // ── Appearance ───────────────────────────────────────────
                    Tab::make(__('admin.settings.tabs.appearance'))
                        ->icon('heroicon-o-paint-brush')
                        ->schema([

                            Section::make(__('admin.settings.sections.color_theme'))
                                ->description('Choose a color scheme for the admin panel. Save and refresh to apply.')
                                ->schema([
                                    Radio::make('admin_theme')
                                        ->label('Admin Panel Theme')
                                        ->helperText('The selected theme applies to all admin panel pages.')
                                        ->options(
                                            collect(AdminPanelProvider::$themes)
                                                ->mapWithKeys(fn ($t, $key) => [$key => $t['label']])
                                                ->toArray()
                                        )
                                        ->columns(4)
                                        ->required(),
                                ]),

                            Section::make(__('admin.settings.sections.panel_mode'))
                                ->description('Control the light/dark mode of the admin panel shell.')
                                ->schema([
                                    Radio::make('admin_panel_theme_mode')
                                        ->label('Admin Panel Mode')
                                        ->helperText('Changes take effect after saving and refreshing.')
                                        ->options([
                                            'light'         => 'Light',
                                            'dark'          => 'Dark',
                                            'system'        => 'System',
                                            'high_contrast' => 'High Contrast',
                                            'sepia'         => 'Sepia',
                                            'midnight'      => 'Midnight',
                                        ])
                                        ->descriptions([
                                            'light'         => 'Always show the admin panel in light mode.',
                                            'dark'          => 'Always show the admin panel in dark mode.',
                                            'system'        => "Follow the user's OS dark/light preference.",
                                            'high_contrast' => 'Stronger contrast dark mode for better accessibility.',
                                            'sepia'         => 'Warm light theme with a soft paper-like tone.',
                                            'midnight'      => 'Deeper blue-dark shell for a premium look.',
                                        ])
                                        ->inline()
                                        ->required(),
                                ]),

                            Section::make(__('admin.settings.sections.auth_bg'))
                                ->description('Customize the branding panel shown on the login page.')
                                ->schema([
                                    Radio::make('auth_theme_mode')
                                        ->label('Auth Panel Style')
                                        ->helperText('Visual style of the login branding panel.')
                                        ->options(
                                            collect(AdminPanelProvider::$authThemeModes)
                                                ->mapWithKeys(fn ($t, $key) => [$key => $t['label']])
                                                ->toArray()
                                        )
                                        ->descriptions(
                                            collect(AdminPanelProvider::$authThemeModes)
                                                ->mapWithKeys(fn ($t, $key) => [$key => $t['description']])
                                                ->toArray()
                                        )
                                        ->inline()
                                        ->required(),

                                    Radio::make('auth_background')
                                        ->label('Auth Background Preset')
                                        ->helperText('Choose a gradient preset for the login page branding panel.')
                                        ->options(
                                            ['inherit' => 'Inherit admin theme']
                                            + collect(AdminPanelProvider::$authBackgrounds)
                                                ->mapWithKeys(fn ($t, $key) => [$key => $t['label']])
                                                ->toArray()
                                        )
                                        ->descriptions([
                                            'inherit'  => 'Use the selected admin theme as the auth background.',
                                            'midnight' => 'Deep charcoal with subtle blue accents.',
                                            'amber'    => 'Warm auction-house gold and black.',
                                            'indigo'   => 'Premium blue-violet with a cool finish.',
                                            'emerald'  => 'Dark green with a refined, modern feel.',
                                            'violet'   => 'Rich purple tones with a luxury look.',
                                            'sky'      => 'Cool blue gradient with a bright accent.',
                                            'rose'     => 'Bold, energetic, and a little dramatic.',
                                            'teal'     => 'Calm teal with a polished enterprise feel.',
                                            'sunset'   => 'Amber-to-rose warmth with high contrast.',
                                            'graphite' => 'Neutral dark gray with a disciplined feel.',
                                            'forest'   => 'Deep green with a premium organic feel.',
                                            'ocean'    => 'Blue-teal blend with a clean modern finish.',
                                            'lavender' => 'Soft purple with a premium, calm atmosphere.',
                                            'gold'     => 'Bright gold with a classic, bold vibe.',
                                        ])
                                        ->columns(3)
                                        ->required(),
                                ]),

                            Section::make(__('admin.settings.sections.branding'))
                                ->description('Upload logos and images. Run `php artisan storage:link` if images do not appear.')
                                ->schema([
                                    Grid::make(4)->schema([
                                        FileUpload::make('app_logo')
                                            ->label('Application Logo')
                                            ->image()
                                            ->disk('public')
                                            ->directory('settings/branding')
                                            ->visibility('public')
                                            ->helperText('Shown in the sidebar header.'),

                                        FileUpload::make('app_icon')
                                            ->label('App Icon / Favicon')
                                            ->image()
                                            ->disk('public')
                                            ->directory('settings/branding')
                                            ->visibility('public')
                                            ->acceptedFileTypes(['image/x-icon', 'image/png', 'image/svg+xml'])
                                            ->helperText('Browser tab icon.'),

                                        FileUpload::make('login_image')
                                            ->label('Login Page Image')
                                            ->image()
                                            ->disk('public')
                                            ->directory('settings/branding')
                                            ->visibility('public')
                                            ->helperText('Subtle background on the auth branding panel.'),

                                        FileUpload::make('favicon')
                                            ->label('Favicon (alternative)')
                                            ->image()
                                            ->disk('public')
                                            ->directory('settings/branding')
                                            ->visibility('public')
                                            ->acceptedFileTypes(['image/x-icon', 'image/png', 'image/svg+xml'])
                                            ->helperText('Overrides the app icon for browser tabs.'),
                                    ]),
                                ]),
                        ]),

                    // ── WhatsApp ──────────────────────────────────────────────
                    Tab::make(__('admin.settings.tabs.whatsapp'))
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->schema([
                            Section::make(__('admin.settings.sections.wa_api'))
                                ->description('Configure your Meta WhatsApp Business API credentials.')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('whatsapp_phone_number_id')
                                            ->label('Phone Number ID')
                                            ->helperText('Found in Meta Business Suite → WhatsApp → API Setup.')
                                            ->maxLength(100),

                                        TextInput::make('whatsapp_api_version')
                                            ->label('API Version')
                                            ->placeholder('v22.0')
                                            ->maxLength(10),
                                    ]),

                                    TextInput::make('whatsapp_access_token')
                                        ->label('Access Token')
                                        ->password()
                                        ->revealable()
                                        ->maxLength(500)
                                        ->autocomplete('new-password')
                                        ->helperText('Permanent or temporary access token from Meta.'),

                                    TextInput::make('whatsapp_verify_token')
                                        ->label('Webhook Verify Token')
                                        ->password()
                                        ->revealable()
                                        ->maxLength(255)
                                        ->helperText('Secret string used to verify incoming webhook requests.'),
                                ]),
                        ]),

                    // ── Claude AI ─────────────────────────────────────────────
                    Tab::make(__('admin.settings.tabs.claude'))
                        ->icon('heroicon-o-sparkles')
                        ->schema([
                            Section::make(__('admin.settings.sections.claude_api'))
                                ->description('Configure the Claude AI model used for intelligent responses.')
                                ->schema([
                                    TextInput::make('claude_api_key')
                                        ->label('API Key')
                                        ->password()
                                        ->revealable()
                                        ->maxLength(255)
                                        ->autocomplete('new-password')
                                        ->helperText('Your Anthropic API key from console.anthropic.com.'),

                                    Grid::make(3)->schema([
                                        Select::make('claude_model')
                                            ->label('Model')
                                            ->options([
                                                'claude-haiku-4-5-20251001' => 'Haiku 4.5 (Fast & Cheap)',
                                                'claude-sonnet-4-6'         => 'Sonnet 4.6 (Balanced)',
                                                'claude-opus-4-8'           => 'Opus 4.8 (Most Capable)',
                                            ])
                                            ->required(),

                                        TextInput::make('claude_max_tokens')
                                            ->label('Max Tokens')
                                            ->numeric()
                                            ->minValue(256)
                                            ->maxValue(8192)
                                            ->helperText('Maximum tokens per response.'),

                                        TextInput::make('claude_temperature')
                                            ->label('Temperature')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(1)
                                            ->step(0.1)
                                            ->helperText('0 = deterministic, 1 = creative.'),
                                    ]),
                                ]),
                        ]),

                    // ── Bot Behaviour ─────────────────────────────────────────
                    Tab::make(__('admin.settings.tabs.bot'))
                        ->icon('heroicon-o-cpu-chip')
                        ->schema([
                            Section::make(__('admin.settings.sections.response'))->schema([
                                TextInput::make('faq_confidence_threshold')
                                    ->label('FAQ Confidence Threshold')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1)
                                    ->step(0.05)
                                    ->helperText('Minimum similarity score (0–1) to match a FAQ. Lower = more permissive.'),

                                Textarea::make('bot_welcome_message')
                                    ->label('Welcome Message')
                                    ->rows(2)
                                    ->maxLength(1000)
                                    ->helperText('Sent when a new conversation starts.'),

                                Textarea::make('bot_fallback_message')
                                    ->label('Fallback Message')
                                    ->rows(2)
                                    ->maxLength(1000)
                                    ->helperText('Sent when the bot cannot understand the user.'),
                            ]),
                        ]),

                    // ── Email ─────────────────────────────────────────────────
                    Tab::make(__('admin.settings.tabs.email'))
                        ->icon('heroicon-o-envelope')
                        ->schema([
                            Section::make(__('admin.settings.sections.mail_sender'))->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('mail_from_name')
                                        ->label('From Name')
                                        ->required()
                                        ->maxLength(100),

                                    TextInput::make('mail_from_address')
                                        ->label('From Address')
                                        ->email()
                                        ->required()
                                        ->maxLength(255),
                                ]),
                            ]),
                        ]),

                ])->persistTabInQueryString('tab'),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $groups = [
            'app_name'                 => 'general',
            'app_tagline'              => 'general',
            'support_email'            => 'general',
            'maintenance_mode'         => 'general',
            'admin_locale'             => 'general',
            'admin_theme'              => 'appearance',
            'admin_panel_theme_mode'   => 'appearance',
            'auth_theme_mode'          => 'appearance',
            'auth_background'          => 'appearance',
            'app_logo'                 => 'appearance',
            'app_icon'                 => 'appearance',
            'login_image'              => 'appearance',
            'favicon'                  => 'appearance',
            'whatsapp_phone_number_id' => 'whatsapp',
            'whatsapp_access_token'    => 'whatsapp',
            'whatsapp_verify_token'    => 'whatsapp',
            'whatsapp_api_version'     => 'whatsapp',
            'claude_api_key'           => 'claude',
            'claude_model'             => 'claude',
            'claude_max_tokens'        => 'claude',
            'claude_temperature'       => 'claude',
            'faq_confidence_threshold' => 'bot',
            'bot_welcome_message'      => 'bot',
            'bot_fallback_message'     => 'bot',
            'mail_from_name'           => 'email',
            'mail_from_address'        => 'email',
        ];

        foreach ($data as $key => $value) {
            Setting::set($key, $value ?? '', $groups[$key] ?? 'general');
        }

        // Apply new default locale immediately for this session (only if user hasn't overridden it)
        if (! session()->has('admin_locale') && isset($data['admin_locale'])) {
            app()->setLocale($data['admin_locale']);
        }

        // Flash session values so the branding panel updates immediately on redirect
        $authBackground = $data['auth_background'] ?? 'inherit';

        if ($authBackground === 'inherit' || blank($authBackground)) {
            $authBackground = AdminPanelProvider::resolveAuthBackgroundKey($data['admin_theme'] ?? 'amber');
        }

        session()->flash('filament_auth_background', $authBackground);

        Notification::make()
            ->success()
            ->title(__('admin.settings.saved'))
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin.settings.save'))
                ->submit('save'),
        ];
    }
}
