<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('admin_locale')
            ?? Setting::get('admin_locale', config('locales.default', 'en'));

        $available = array_keys(config('locales.available', ['en' => 'English']));

        if (in_array($locale, $available)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
