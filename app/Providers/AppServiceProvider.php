<?php

namespace App\Providers;

use App\Models\Setting;
use App\Policies\RolePolicy;
use App\Services\Maria\MariaPermissionVisibility;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
        $this->applyRegionalSettings();
        MariaPermissionVisibility::apply();
        $this->configureRegionalFormComponents();
        $this->applyMailSettings();
        $this->configureRateLimiting();
    }

    private function configureRegionalFormComponents(): void
    {
        DateTimePicker::configureUsing(function (DateTimePicker $component): void {
            $component
                ->native(false)
                ->displayFormat(config('app.display_datetime_format', 'd/m/Y H:i'))
                ->timezone(config('app.timezone', 'UTC'));
        });

        DatePicker::configureUsing(function (DatePicker $component): void {
            $component
                ->native(false)
                ->displayFormat(config('app.display_date_format', 'd/m/Y'));
        });
    }

    private function applyRegionalSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $timezone = (string) Setting::get('app_timezone', config('app.timezone', 'UTC'));
        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = (string) config('app.timezone', 'UTC');
        }

        config([
            'app.timezone' => $timezone,
            'app.display_date_format' => Setting::get('app_date_format', 'd/m/Y'),
            'app.display_datetime_format' => Setting::get('app_datetime_format', 'd/m/Y H:i'),
        ]);
        date_default_timezone_set($timezone);
    }

    /**
     * Laravel's default `throttle:N,1` keys attempts by domain+IP only, not by route
     * (see ThrottleRequests::resolveRequestSignature) — so WhatsApp, Messenger, and
     * Instagram webhook routes would otherwise all drain the same bucket, since Meta
     * delivers every channel's webhooks from overlapping IP ranges. Keying by path as
     * well gives each webhook route (and each account behind it) its own 120/min budget.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('webhook', function (Request $request) {
            return Limit::perMinute(120)->by($request->path().'|'.$request->ip());
        });
    }

    /**
     * Mail config (unlike WhatsApp/Claude) is read by Laravel's mailer once per
     * process and cached internally, so it must be pushed into config() at boot
     * rather than read on demand.
     */
    private function applyMailSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        config([
            'mail.from.name' => Setting::get('mail_from_name', config('mail.from.name')),
            'mail.from.address' => Setting::get('mail_from_address', config('mail.from.address')),
        ]);

        if ($host = Setting::get('mail_host')) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $host,
                'mail.mailers.smtp.port' => Setting::get('mail_port', 587),
                'mail.mailers.smtp.username' => Setting::get('mail_username'),
                'mail.mailers.smtp.password' => Setting::get('mail_password'),
                'mail.mailers.smtp.scheme' => Setting::get('mail_encryption') ?: null,
            ]);
        }
    }
}
