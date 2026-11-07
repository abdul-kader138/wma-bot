<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;

/**
 * One global schedule (System Settings → Bot Behaviour), not per channel account — the
 * org picked a single set of hours across WhatsApp/Messenger/Instagram rather than
 * configuring each channel separately. Used to decide which escalation message a
 * customer gets and whether to flag a notification as after-hours.
 */
class BusinessHours
{
    public static function isOpenNow(): bool
    {
        if (! Setting::get('business_hours_enabled', false)) {
            return true;
        }

        $timezone = Setting::get('business_hours_timezone', config('app.timezone', 'UTC'));
        $now = Carbon::now($timezone);

        $days = Setting::get('business_hours_days', ['mon', 'tue', 'wed', 'thu', 'fri']);
        if (! in_array(strtolower($now->format('D')), $days, true)) {
            return false;
        }

        // Normalized through Carbon rather than compared as raw strings: the TimePicker
        // form field may round-trip values with or without seconds (e.g. "09:00:00" vs
        // "09:00"), and a raw string comparison would get those slightly wrong.
        $start = Carbon::parse((string) Setting::get('business_hours_start', '09:00'))->format('H:i');
        $end = Carbon::parse((string) Setting::get('business_hours_end', '18:00'))->format('H:i');
        $time = $now->format('H:i');

        return $time >= $start && $time <= $end;
    }
}
