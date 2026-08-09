<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Same alert channel as BotJobFailedNotification/NewServiceRequestNotification —
        // a queue that's falling behind (several customers messaging at once, more
        // than the current worker count can keep up with) is exactly the kind of
        // thing staff should hear about the same way they hear about a failed job.
        //
        // Guarded: this provider boots on every artisan command, including the very
        // first `migrate` on a fresh install, before the settings table exists.
        try {
            if ($email = Setting::get('staff_notification_email')) {
                Horizon::routeMailNotificationsTo($email);
            }
        } catch (\Throwable) {
            // Settings table not migrated yet — nothing to route to regardless.
        }
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            if (! $user) {
                return false;
            }

            $superAdminName = (string) config('filament-shield.super_admin.name', 'super_admin');

            return $user->hasRole($superAdminName);
        });
    }
}
