<?php

namespace App\Providers\Filament;

use App\Filament\Auth\EditProfile;
use App\Filament\Auth\Login;
use App\Http\Middleware\SetAdminLocale;
use App\Models\Setting;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Support\Facades\Route;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    // ── Available admin color themes ──────────────────────────────────────────
    public static array $themes = [
        'amber'   => ['label' => 'Amber Gold',       'color' => 'amber'],
        'indigo'  => ['label' => 'Indigo',            'color' => 'indigo'],
        'emerald' => ['label' => 'Emerald',           'color' => 'emerald'],
        'rose'    => ['label' => 'Rose',              'color' => 'rose'],
        'violet'  => ['label' => 'Violet',            'color' => 'violet'],
        'sky'     => ['label' => 'Sky Blue',          'color' => 'sky'],
        'teal'    => ['label' => 'Teal',              'color' => 'teal'],
        'orange'  => ['label' => 'Orange',            'color' => 'orange'],
        'slate'   => ['label' => 'Slate',             'color' => 'slate'],
        'gray'    => ['label' => 'Gray',              'color' => 'gray'],
        'blue'    => ['label' => 'Blue',              'color' => 'blue'],
        'cyan'    => ['label' => 'Cyan',              'color' => 'cyan'],
        'purple'  => ['label' => 'Purple',            'color' => 'purple'],
        'pink'    => ['label' => 'Pink',              'color' => 'pink'],
    ];

    // ── Auth background presets ───────────────────────────────────────────────
    public static array $authBackgrounds = [
        'midnight' => ['label' => 'Midnight',       'start' => '9 9 11',    'mid' => '15 15 20',   'end' => '22 22 30',    'accent' => '160 160 255'],
        'amber'    => ['label' => 'Amber',          'start' => '20 15 10',  'mid' => '44 24 8',    'end' => '92 45 8',     'accent' => '245 158 11'],
        'indigo'   => ['label' => 'Indigo',         'start' => '12 14 32',  'mid' => '24 28 60',   'end' => '55 48 140',   'accent' => '129 140 248'],
        'emerald'  => ['label' => 'Emerald',        'start' => '8 20 16',   'mid' => '10 34 26',   'end' => '16 52 38',    'accent' => '52 211 153'],
        'violet'   => ['label' => 'Violet',         'start' => '16 10 26',  'mid' => '32 16 52',   'end' => '68 24 96',    'accent' => '192 132 252'],
        'sky'      => ['label' => 'Sky',            'start' => '10 18 34',  'mid' => '12 35 64',   'end' => '14 68 104',   'accent' => '56 189 248'],
        'rose'     => ['label' => 'Rose',           'start' => '28 8 16',   'mid' => '52 12 28',   'end' => '96 16 40',    'accent' => '244 114 182'],
        'teal'     => ['label' => 'Teal',           'start' => '8 22 24',   'mid' => '8 40 42',    'end' => '12 72 76',    'accent' => '45 212 191'],
        'sunset'   => ['label' => 'Sunset',         'start' => '32 14 10',  'mid' => '76 24 18',   'end' => '120 34 28',   'accent' => '251 146 60'],
        'graphite' => ['label' => 'Graphite',       'start' => '18 18 20',  'mid' => '28 28 32',   'end' => '40 40 46',    'accent' => '148 163 184'],
        'forest'   => ['label' => 'Forest',         'start' => '10 25 18',  'mid' => '14 42 28',   'end' => '20 63 40',    'accent' => '74 222 128'],
        'ocean'    => ['label' => 'Ocean',          'start' => '8 20 30',   'mid' => '8 48 72',    'end' => '14 116 144',  'accent' => '34 211 238'],
        'lavender' => ['label' => 'Lavender',       'start' => '26 18 48',  'mid' => '52 32 96',   'end' => '99 73 191',   'accent' => '196 181 253'],
        'gold'     => ['label' => 'Gold',           'start' => '38 24 10',  'mid' => '87 54 14',   'end' => '161 98 7',    'accent' => '251 191 36'],
    ];

    // ── Auth panel styles (panel card style, not background) ──────────────────
    public static array $authThemeModes = [
        'dark'    => ['label' => 'Dark',    'description' => 'Classic dark gradient panel.'],
        'light'   => ['label' => 'Light',   'description' => 'Clean white panel.'],
        'glass'   => ['label' => 'Glass',   'description' => 'Frosted glass blur over gradient.'],
        'vibrant' => ['label' => 'Vibrant', 'description' => 'Bold accent-color dominant.'],
        'minimal' => ['label' => 'Minimal', 'description' => 'Flat solid dark panel.'],
        'auto'    => ['label' => 'Auto',    'description' => 'Follows OS dark/light preference.'],
    ];

    // ── Resolve primary color from stored setting ─────────────────────────────
    protected static function resolveThemeColors(): array
    {
        $colorMap = [
            'amber'   => Color::Amber,
            'indigo'  => Color::Indigo,
            'emerald' => Color::Emerald,
            'rose'    => Color::Rose,
            'violet'  => Color::Violet,
            'sky'     => Color::Sky,
            'teal'    => Color::Teal,
            'orange'  => Color::Orange,
            'slate'   => Color::Slate,
            'gray'    => Color::Gray,
            'blue'    => Color::Blue,
            'cyan'    => Color::Cyan,
            'purple'  => Color::Purple,
            'pink'    => Color::Pink,
        ];

        try {
            $theme = Setting::get('admin_theme', 'amber');
        } catch (\Throwable) {
            $theme = 'amber';
        }

        return ['primary' => $colorMap[$theme] ?? Color::Amber];
    }

    // ── Map auth background key from admin theme ──────────────────────────────
    public static function resolveAuthBackgroundKey(?string $theme = null): string
    {
        $theme ??= 'amber';

        return match ($theme) {
            'slate', 'gray'  => 'graphite',
            'blue'           => 'indigo',
            'cyan'           => 'ocean',
            'purple'         => 'violet',
            'pink'           => 'rose',
            'orange'         => 'sunset',
            default          => array_key_exists($theme, self::$authBackgrounds) ? $theme : 'midnight',
        };
    }

    // ── Panel dark-mode resolution ────────────────────────────────────────────
    public static function resolveNativeThemeModeKey(?string $mode = null): string
    {
        return match ($mode) {
            'light', 'sepia'              => 'light',
            'dark', 'high_contrast', 'midnight' => 'dark',
            'system'                      => 'system',
            default                       => 'dark',
        };
    }

    protected static function resolveDefaultThemeMode(): ThemeMode
    {
        try {
            $mode = Setting::get('admin_panel_theme_mode', 'dark');
        } catch (\Throwable) {
            $mode = 'dark';
        }

        return match (self::resolveNativeThemeModeKey($mode)) {
            'light'  => ThemeMode::Light,
            'system' => ThemeMode::System,
            default  => ThemeMode::Dark,
        };
    }

    protected static function resolveDarkModeArgs(): array
    {
        try {
            $mode = Setting::get('admin_panel_theme_mode', 'dark');
        } catch (\Throwable) {
            $mode = 'dark';
        }

        return match (self::resolveNativeThemeModeKey($mode)) {
            'light'  => ['condition' => false, 'isForced' => false],
            'system' => ['condition' => true,  'isForced' => false],
            default  => ['condition' => true,  'isForced' => true],
        };
    }

    // ── Extra CSS injected after Filament styles (panel mode overrides) ───────
    protected static function resolveAdminPanelModeStyles(): string
    {
        try {
            $mode = Setting::get('admin_panel_theme_mode', 'dark');
        } catch (\Throwable) {
            $mode = 'dark';
        }

        $custom = match ($mode) {
            'high_contrast' => <<<'CSS'
<style>
    .fi-body {
        background: #020617 !important;
        color: #f8fafc !important;
        color-scheme: dark;
    }
    .fi-topbar, .fi-sidebar, .fi-header, .fi-page, .fi-main, .fi-simple-main {
        background: #020617 !important;
        border-color: rgba(148,163,184,.22) !important;
    }
    .fi-sidebar-item-label, .fi-sidebar-group-label { color: #e2e8f0 !important; }
</style>
CSS,
            'sepia' => <<<'CSS'
<style>
    .fi-body {
        background: linear-gradient(180deg, #f7efe4 0%, #efe3d2 100%) !important;
        color: #4b3621 !important;
        color-scheme: light;
    }
    .fi-topbar, .fi-sidebar, .fi-header, .fi-page, .fi-main, .fi-simple-main {
        background: #f9f2e8 !important;
        border-color: rgba(120,86,56,.16) !important;
    }
</style>
CSS,
            'midnight' => <<<'CSS'
<style>
    .fi-body {
        background: linear-gradient(180deg, #020617 0%, #0f172a 55%, #111827 100%) !important;
        color: #e5e7eb !important;
        color-scheme: dark;
    }
    .fi-topbar, .fi-sidebar, .fi-header, .fi-page, .fi-main, .fi-simple-main {
        background: rgba(15,23,42,.96) !important;
        border-color: rgba(96,165,250,.18) !important;
    }
</style>
CSS,
            default => '',
        };

        // Always hide the built-in theme switcher — we manage it via System Settings
        return <<<'CSS'
<style>.fi-theme-switcher { display: none !important; }</style>
CSS . $custom;
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->profile(EditProfile::class, isSimple: false)
            ->brandName(fn () => Setting::get('app_name', config('app.name')))
            ->colors(self::resolveThemeColors())
            ->defaultThemeMode(self::resolveDefaultThemeMode())
            ->darkMode(...array_values(self::resolveDarkModeArgs()))

            // ── Auth split-panel branding ──────────────────────────────────
            ->renderHook(
                PanelsRenderHook::SIMPLE_PAGE_START,
                fn () => view('components.auth-brand-panel'),
                scopes: [Login::class],
            )

            // ── Language switcher in topbar ───────────────────────────────────
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn () => view('components.language-switcher'),
            )

            // ── Locale switcher route ─────────────────────────────────────────
            ->routes(function () {
                Route::get(
                    '/set-locale/{locale}',
                    function (string $locale) {
                        $available = array_keys(config('locales.available', []));
                        if (in_array($locale, $available)) {
                            session(['admin_locale' => $locale]);
                        }
                        return redirect()->back();
                    }
                )->name('set-locale');
            })

            // ── Inject panel mode CSS (high contrast, sepia, midnight, etc.) ─
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => self::resolveAdminPanelModeStyles(),
            )

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->gridColumns(['default' => 1, 'sm' => 2, 'lg' => 3])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns(['default' => 1, 'sm' => 2, 'lg' => 2])
                    ->resourceCheckboxListColumns(['default' => 1, 'sm' => 2]),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetAdminLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
