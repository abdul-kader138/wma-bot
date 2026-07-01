<?php

namespace App\Providers;

use App\Models\Setting;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
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
        $this->applyMailSettings();
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
            'mail.from.name'    => Setting::get('mail_from_name', config('mail.from.name')),
            'mail.from.address' => Setting::get('mail_from_address', config('mail.from.address')),
        ]);

        if ($host = Setting::get('mail_host')) {
            config([
                'mail.default'                 => 'smtp',
                'mail.mailers.smtp.host'       => $host,
                'mail.mailers.smtp.port'       => Setting::get('mail_port', 587),
                'mail.mailers.smtp.username'   => Setting::get('mail_username'),
                'mail.mailers.smtp.password'   => Setting::get('mail_password'),
                'mail.mailers.smtp.scheme'     => Setting::get('mail_encryption') ?: null,
            ]);
        }
    }
}
