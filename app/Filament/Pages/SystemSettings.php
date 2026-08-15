<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Providers\Filament\AdminPanelProvider;
use App\Services\Maria\MariaPermissionVisibility;
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

    protected static ?int $navigationSort = 99;

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
            'app_name' => Setting::get('app_name', config('app.name', 'WMA Bot')),
            'app_tagline' => Setting::get('app_tagline', ''),
            'support_email' => Setting::get('support_email', ''),
            'maintenance_mode' => Setting::get('maintenance_mode', false),
            'admin_locale' => Setting::get('admin_locale', config('locales.default', 'en')),
            'app_timezone' => Setting::get('app_timezone', config('app.timezone', 'UTC')),
            'app_date_format' => Setting::get('app_date_format', 'd/m/Y'),
            'app_datetime_format' => Setting::get('app_datetime_format', 'd/m/Y H:i'),

            // Security
            'two_factor_enabled' => Setting::get('two_factor_enabled', true),
            'maria_assistant_enabled' => Setting::get('maria_assistant_enabled', false),

            // Appearance
            'admin_theme' => Setting::get('admin_theme', 'amber'),
            'admin_panel_theme_mode' => Setting::get('admin_panel_theme_mode', 'dark'),
            'auth_theme_mode' => Setting::get('auth_theme_mode', 'dark'),
            'auth_background' => Setting::get('auth_background', 'inherit'),
            'app_logo' => Setting::get('app_logo'),
            'app_icon' => Setting::get('app_icon'),
            'login_image' => Setting::get('login_image'),
            'favicon' => Setting::get('favicon'),

            // WhatsApp / Messenger / Instagram
            'whatsapp_verify_token' => Setting::get('whatsapp_verify_token', env('WHATSAPP_VERIFY_TOKEN', '')),
            'messenger_verify_token' => Setting::get('messenger_verify_token', env('MESSENGER_VERIFY_TOKEN', '')),
            'instagram_verify_token' => Setting::get('instagram_verify_token', env('INSTAGRAM_VERIFY_TOKEN', '')),

            // Claude AI (secret intentionally never hydrated into the form)
            'claude_api_key' => '',
            'claude_model' => Setting::get('claude_model', env('CLAUDE_MODEL', 'claude-haiku-4-5-20251001')),
            'claude_max_tokens' => Setting::get('claude_max_tokens', 1024),
            'claude_temperature' => Setting::get('claude_temperature', 0.7),
            'claude_rate_limit_per_minute' => Setting::get('claude_rate_limit_per_minute', 50),
            'claude_max_messages_per_session' => Setting::get('claude_max_messages_per_session', 20),
            'claude_max_messages_per_day' => Setting::get('claude_max_messages_per_day', 100),
            'claude_daily_global_cap' => Setting::get('claude_daily_global_cap', 0),

            // Google Workspace (secret intentionally never hydrated into the form)
            'google_client_id' => Setting::get('google_client_id', config('services.google.client_id', '')),
            'google_client_secret' => '',
            'google_redirect_uri' => Setting::get('google_redirect_uri', config('services.google.redirect_uri', '')),

            // Bot Behaviour
            'faq_confidence_threshold' => Setting::get('faq_confidence_threshold', 0.7),
            'bot_welcome_message' => Setting::get('bot_welcome_message', [
                'en' => 'Hello! How can I help you today?',
                'it' => 'Ciao! Come posso aiutarti oggi?',
                'bn' => 'হ্যালো! আজ আমি আপনাকে কীভাবে সাহায্য করতে পারি?',
            ]),
            'bot_fallback_message' => Setting::get('bot_fallback_message', [
                'en' => "I'm sorry, I don't understand. Please contact our support team.",
                'it' => 'Mi dispiace, non ho capito. Contatta il nostro team di supporto.',
                'bn' => 'দুঃখিত, আমি বুঝতে পারিনি। আমাদের সাপোর্ট টিমের সাথে যোগাযোগ করুন।',
            ]),
            'claude_session_limit_message' => Setting::get('claude_session_limit_message', [
                'en' => "You've reached the message limit for this session. Please type \"menu\" to start a new conversation.",
                'it' => 'Hai raggiunto il limite di messaggi per questa sessione. Digita "menu" per iniziare una nuova conversazione.',
                'bn' => 'আপনি এই সেশনের মেসেজ সীমায় পৌঁছেছেন। নতুন কথোপকথন শুরু করতে "menu" লিখুন।',
            ]),
            'claude_daily_limit_message' => Setting::get('claude_daily_limit_message', [
                'en' => "You've reached today's message limit. Please contact our support team, or try again tomorrow.",
                'it' => 'Hai raggiunto il limite di messaggi di oggi. Contatta il nostro supporto o riprova domani.',
                'bn' => 'আপনি আজকের মেসেজ সীমায় পৌঁছেছেন। আমাদের সাপোর্ট টিমের সাথে যোগাযোগ করুন, অথবা আগামীকাল আবার চেষ্টা করুন।',
            ]),

            // Email
            'mail_from_name' => Setting::get('mail_from_name', config('mail.from.name', '')),
            'mail_from_address' => Setting::get('mail_from_address', config('mail.from.address', '')),
            'mail_host' => Setting::get('mail_host', ''),
            'mail_port' => Setting::get('mail_port', 587),
            'mail_username' => Setting::get('mail_username', ''),
            'mail_password' => Setting::get('mail_password', ''),
            'mail_encryption' => Setting::get('mail_encryption', 'tls'),
            'staff_notification_email' => Setting::get('staff_notification_email', ''),
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

                                Toggle::make('maria_assistant_enabled')
                                    ->label('Enable Maria Assistant')
                                    ->helperText('When disabled, Maria menus, pages, private assistant routing, connectors, and scheduled workflows are unavailable. Users also need the Access Maria Assistant role permission.'),

                                Select::make('admin_locale')
                                    ->label(__('admin.settings.fields.default_language'))
                                    ->helperText(__('admin.settings.fields.default_language_help'))
                                    ->options(config('locales.available', []))
                                    ->default(config('locales.default', 'en'))
                                    ->required()
                                    ->native(false),

                                Grid::make(3)->schema([
                                    Select::make('app_timezone')
                                        ->label('Application Timezone')
                                        ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                                        ->searchable()
                                        ->required()
                                        ->helperText('Global timezone for the application. Maria profiles may override it for personal schedules.'),
                                    Select::make('app_date_format')
                                        ->label('Date Format')
                                        ->options([
                                            'd/m/Y' => 'DD/MM/YYYY (31/12/2026)',
                                            'm/d/Y' => 'MM/DD/YYYY (12/31/2026)',
                                            'Y-m-d' => 'YYYY-MM-DD (2026-12-31)',
                                            'd.m.Y' => 'DD.MM.YYYY (31.12.2026)',
                                        ])->required()->native(false),
                                    Select::make('app_datetime_format')
                                        ->label('Date & Time Format')
                                        ->options([
                                            'd/m/Y H:i' => 'DD/MM/YYYY 24-hour',
                                            'd/m/Y h:i A' => 'DD/MM/YYYY 12-hour',
                                            'm/d/Y h:i A' => 'MM/DD/YYYY 12-hour',
                                            'Y-m-d H:i' => 'YYYY-MM-DD 24-hour',
                                            'd.m.Y H:i' => 'DD.MM.YYYY 24-hour',
                                        ])->required()->native(false),
                                ]),
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
                                            'light' => 'Light',
                                            'dark' => 'Dark',
                                            'system' => 'System',
                                            'high_contrast' => 'High Contrast',
                                            'sepia' => 'Sepia',
                                            'midnight' => 'Midnight',
                                        ])
                                        ->descriptions([
                                            'light' => 'Always show the admin panel in light mode.',
                                            'dark' => 'Always show the admin panel in dark mode.',
                                            'system' => "Follow the user's OS dark/light preference.",
                                            'high_contrast' => 'Stronger contrast dark mode for better accessibility.',
                                            'sepia' => 'Warm light theme with a soft paper-like tone.',
                                            'midnight' => 'Deeper blue-dark shell for a premium look.',
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
                                            'inherit' => 'Use the selected admin theme as the auth background.',
                                            'midnight' => 'Deep charcoal with subtle blue accents.',
                                            'amber' => 'Warm auction-house gold and black.',
                                            'indigo' => 'Premium blue-violet with a cool finish.',
                                            'emerald' => 'Dark green with a refined, modern feel.',
                                            'violet' => 'Rich purple tones with a luxury look.',
                                            'sky' => 'Cool blue gradient with a bright accent.',
                                            'rose' => 'Bold, energetic, and a little dramatic.',
                                            'teal' => 'Calm teal with a polished enterprise feel.',
                                            'sunset' => 'Amber-to-rose warmth with high contrast.',
                                            'graphite' => 'Neutral dark gray with a disciplined feel.',
                                            'forest' => 'Deep green with a premium organic feel.',
                                            'ocean' => 'Blue-teal blend with a clean modern finish.',
                                            'lavender' => 'Soft purple with a premium, calm atmosphere.',
                                            'gold' => 'Bright gold with a classic, bold vibe.',
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

                    // ── Security ──────────────────────────────────────────────
                    Tab::make(__('admin.settings.tabs.security'))
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            Section::make(__('admin.settings.sections.two_factor'))
                                ->description('Control two-factor authentication for the whole admin panel.')
                                ->schema([
                                    Toggle::make('two_factor_enabled')
                                        ->label(__('admin.settings.fields.two_factor_enabled'))
                                        ->helperText(__('admin.settings.fields.two_factor_enabled_help')),
                                ]),
                        ]),

                    // ── Messaging Channels (WhatsApp / Messenger / Instagram) ──
                    Tab::make(__('admin.settings.tabs.whatsapp'))
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->schema([
                            Section::make(__('admin.settings.sections.wa_api'))
                                ->description('This token verifies the single webhook callback URL shared by all of your WhatsApp numbers. Manage individual phone numbers and their access tokens under WhatsApp Accounts.')
                                ->schema([
                                    TextInput::make('whatsapp_verify_token')
                                        ->label('Webhook Verify Token')
                                        ->password()
                                        ->revealable()
                                        ->maxLength(255)
                                        ->helperText('Secret string used to verify incoming webhook requests.'),
                                ]),

                            Section::make(__('admin.settings.sections.messenger_api'))
                                ->description('This token verifies the webhook callback URL shared by all of your Messenger pages. Manage individual pages and their access tokens under WhatsApp Accounts (select "Facebook Messenger" as the platform).')
                                ->schema([
                                    TextInput::make('messenger_verify_token')
                                        ->label('Webhook Verify Token')
                                        ->password()
                                        ->revealable()
                                        ->maxLength(255)
                                        ->helperText('Set this same value when subscribing your Meta App\'s webhook to the "messages" field for Messenger. App secret is set via MESSENGER_APP_SECRET in your server\'s .env file.'),
                                ]),

                            Section::make(__('admin.settings.sections.instagram_api'))
                                ->description('This token verifies the webhook callback URL shared by all of your connected Instagram accounts. Manage individual accounts under WhatsApp Accounts (select "Instagram" as the platform).')
                                ->schema([
                                    TextInput::make('instagram_verify_token')
                                        ->label('Webhook Verify Token')
                                        ->password()
                                        ->revealable()
                                        ->maxLength(255)
                                        ->helperText('Set this same value when subscribing your Meta App\'s webhook to the "messages" field for Instagram. App secret is set via INSTAGRAM_APP_SECRET in your server\'s .env file.'),
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
                                        ->helperText(Setting::getSecret('claude_api_key') ? 'An API key is stored. Leave blank to keep it unchanged.' : 'No API key is stored in Settings; the environment fallback will be used.'),

                                    Grid::make(3)->schema([
                                        Select::make('claude_model')
                                            ->label('Model')
                                            ->options([
                                                'claude-haiku-4-5-20251001' => 'Haiku 4.5 (Fast & Cheap)',
                                                'claude-sonnet-4-6' => 'Sonnet 4.6 (Balanced)',
                                                'claude-opus-4-8' => 'Opus 4.8 (Most Capable)',
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

                                    Grid::make(2)->schema([
                                        TextInput::make('claude_rate_limit_per_minute')
                                            ->label('Rate Limit (requests/min)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->required()
                                            ->helperText('Shared across every WhatsApp account and customer — match this to your Anthropic plan\'s requests-per-minute limit. Messages beyond it wait briefly and retry rather than erroring out.'),

                                        TextInput::make('claude_max_messages_per_session')
                                            ->label('Max AI Replies per Session')
                                            ->numeric()
                                            ->minValue(1)
                                            ->required()
                                            ->helperText('Per-conversation cap on paid Claude calls (FAQ answers don\'t count). Once a customer hits this, the session ends and they must type "menu" to start over. Weakest guard — a customer can clear it any time by restarting.'),
                                    ]),

                                    Grid::make(2)->schema([
                                        TextInput::make('claude_max_messages_per_day')
                                            ->label('Max AI Replies per Phone/Day')
                                            ->numeric()
                                            ->minValue(0)
                                            ->required()
                                            ->helperText('Per-customer daily cap, keyed to their phone number so it survives a "menu" restart — this is what actually stops abuse. 0 disables it.'),

                                        TextInput::make('claude_daily_global_cap')
                                            ->label('Daily Claude Call Cap (all customers)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->required()
                                            ->helperText('Absolute ceiling on Claude calls per day across everyone — a circuit breaker for when something is badly wrong. Staff get one email when it trips. 0 disables it (default); set it to match your real daily budget.'),
                                    ]),
                                ]),
                        ]),

                    Tab::make('Google Workspace')
                        ->icon('heroicon-o-cloud')
                        ->schema([
                            Section::make('Google OAuth')
                                ->description('Configure Google Workspace OAuth here. Environment variables remain fallback values. The saved client secret is encrypted and is never displayed again.')
                                ->schema([
                                    TextInput::make('google_client_id')->label('Client ID')->maxLength(500)->autocomplete(false),
                                    TextInput::make('google_client_secret')->label('Client Secret')->password()->revealable()->autocomplete('new-password')->helperText(Setting::get('google_client_secret') ? 'A client secret is stored. Leave blank to keep it unchanged.' : 'No client secret is stored in Settings; the environment fallback will be used.'),
                                    TextInput::make('google_redirect_uri')->label('Redirect URI')->url()->maxLength(1000)->helperText('Copy this exact URI into the Google Cloud OAuth client.'),
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

                                Section::make('Welcome Message')
                                    ->description('Sent right after the customer picks a service, in their chosen language.')
                                    ->schema(
                                        collect(config('services_bot.languages', ['en' => 'English']))
                                            ->map(fn ($name, $code) => Textarea::make("bot_welcome_message.{$code}")
                                                ->label($name)
                                                ->rows(2)
                                                ->maxLength(1000)
                                                ->required($code === 'en')
                                            )->values()->toArray()
                                    )
                                    ->columns(count(config('services_bot.languages', ['en' => 'English']))),

                                Section::make('Fallback Message')
                                    ->description('Sent when Claude cannot produce a reply, in the customer\'s chosen language.')
                                    ->schema(
                                        collect(config('services_bot.languages', ['en' => 'English']))
                                            ->map(fn ($name, $code) => Textarea::make("bot_fallback_message.{$code}")
                                                ->label($name)
                                                ->rows(2)
                                                ->maxLength(1000)
                                                ->required($code === 'en')
                                            )->values()->toArray()
                                    )
                                    ->columns(count(config('services_bot.languages', ['en' => 'English']))),

                                Section::make('Session Limit Message')
                                    ->description('Sent when a customer hits the "Max AI Replies per Session" cap set in the Claude AI tab, in the customer\'s chosen language.')
                                    ->schema(
                                        collect(config('services_bot.languages', ['en' => 'English']))
                                            ->map(fn ($name, $code) => Textarea::make("claude_session_limit_message.{$code}")
                                                ->label($name)
                                                ->rows(2)
                                                ->maxLength(1000)
                                                ->required($code === 'en')
                                            )->values()->toArray()
                                    )
                                    ->columns(count(config('services_bot.languages', ['en' => 'English']))),

                                Section::make('Daily Limit Message')
                                    ->description('Sent when a customer hits the "Max AI Replies per Phone/Day" cap set in the Claude AI tab, in the customer\'s chosen language.')
                                    ->schema(
                                        collect(config('services_bot.languages', ['en' => 'English']))
                                            ->map(fn ($name, $code) => Textarea::make("claude_daily_limit_message.{$code}")
                                                ->label($name)
                                                ->rows(2)
                                                ->maxLength(1000)
                                                ->required($code === 'en')
                                            )->values()->toArray()
                                    )
                                    ->columns(count(config('services_bot.languages', ['en' => 'English']))),
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

                                TextInput::make('staff_notification_email')
                                    ->label('Staff Notification Email')
                                    ->email()
                                    ->maxLength(255)
                                    ->helperText('Where alerts go when a new WhatsApp request comes in, or the bot fails. Leave blank to disable.'),
                            ]),

                            Section::make('SMTP Server')
                                ->description('Configure an SMTP provider (e.g. Brevo, Gmail) to actually deliver emails. Leave the host blank to keep logging emails instead of sending them.')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('mail_host')
                                            ->label('SMTP Host')
                                            ->placeholder('smtp-relay.brevo.com')
                                            ->maxLength(255),

                                        TextInput::make('mail_port')
                                            ->label('SMTP Port')
                                            ->numeric()
                                            ->placeholder('587'),
                                    ]),

                                    Grid::make(2)->schema([
                                        TextInput::make('mail_username')
                                            ->label('SMTP Username')
                                            ->maxLength(255),

                                        TextInput::make('mail_password')
                                            ->label('SMTP Password')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password')
                                            ->maxLength(255),
                                    ]),

                                    Select::make('mail_encryption')
                                        ->label('Encryption')
                                        ->options([
                                            'tls' => 'TLS',
                                            'ssl' => 'SSL',
                                        ])
                                        ->native(false),
                                ]),
                        ]),

                ])->persistTabInQueryString('tab'),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $groups = [
            'app_name' => 'general',
            'app_tagline' => 'general',
            'support_email' => 'general',
            'maintenance_mode' => 'general',
            'admin_locale' => 'general',
            'app_timezone' => 'regional',
            'app_date_format' => 'regional',
            'app_datetime_format' => 'regional',
            'two_factor_enabled' => 'security',
            'maria_assistant_enabled' => 'maria',
            'admin_theme' => 'appearance',
            'admin_panel_theme_mode' => 'appearance',
            'auth_theme_mode' => 'appearance',
            'auth_background' => 'appearance',
            'app_logo' => 'appearance',
            'app_icon' => 'appearance',
            'login_image' => 'appearance',
            'favicon' => 'appearance',
            'whatsapp_verify_token' => 'whatsapp',
            'messenger_verify_token' => 'whatsapp',
            'instagram_verify_token' => 'whatsapp',
            'claude_model' => 'claude',
            'claude_max_tokens' => 'claude',
            'claude_temperature' => 'claude',
            'claude_rate_limit_per_minute' => 'claude',
            'claude_max_messages_per_session' => 'claude',
            'claude_max_messages_per_day' => 'claude',
            'claude_daily_global_cap' => 'claude',
            'google_client_id' => 'google',
            'google_redirect_uri' => 'google',
            'faq_confidence_threshold' => 'bot',
            'bot_welcome_message' => 'bot',
            'bot_fallback_message' => 'bot',
            'claude_session_limit_message' => 'bot',
            'claude_daily_limit_message' => 'bot',
            'mail_from_name' => 'email',
            'mail_from_address' => 'email',
            'mail_host' => 'email',
            'mail_port' => 'email',
            'mail_username' => 'email',
            'mail_password' => 'email',
            'mail_encryption' => 'email',
            'staff_notification_email' => 'email',
        ];

        $googleSecret = trim((string) ($data['google_client_secret'] ?? ''));
        unset($data['google_client_secret']);

        $claudeApiKey = trim((string) ($data['claude_api_key'] ?? ''));
        unset($data['claude_api_key']);

        foreach ($data as $key => $value) {
            Setting::set($key, $value ?? '', $groups[$key] ?? 'general');
        }

        if ($googleSecret !== '') {
            Setting::setSecret('google_client_secret', $googleSecret, 'google');
        }

        if ($claudeApiKey !== '') {
            Setting::setSecret('claude_api_key', $claudeApiKey, 'claude');
        }
        MariaPermissionVisibility::apply();

        // Apply new default locale immediately for this session (only if user hasn't overridden it)
        if (! session()->has('admin_locale') && isset($data['admin_locale'])) {
            app()->setLocale($data['admin_locale']);
        }

        if (isset($data['app_timezone']) && in_array($data['app_timezone'], timezone_identifiers_list(), true)) {
            config(['app.timezone' => $data['app_timezone']]);
            date_default_timezone_set($data['app_timezone']);
        }
        config([
            'app.display_date_format' => $data['app_date_format'] ?? 'd/m/Y',
            'app.display_datetime_format' => $data['app_datetime_format'] ?? 'd/m/Y H:i',
        ]);

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
