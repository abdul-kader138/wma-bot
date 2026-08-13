<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Providers\AppServiceProvider;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionalSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_timezone_and_formats_are_applied_at_application_boot(): void
    {
        Setting::set('app_timezone', 'Europe/Berlin', 'regional');
        Setting::set('app_date_format', 'd.m.Y', 'regional');
        Setting::set('app_datetime_format', 'd.m.Y H:i', 'regional');

        (new AppServiceProvider(app()))->boot();

        $this->assertSame('Europe/Berlin', config('app.timezone'));
        $this->assertSame('Europe/Berlin', date_default_timezone_get());
        $this->assertSame('d.m.Y', config('app.display_date_format'));
        $this->assertSame('d.m.Y H:i', config('app.display_datetime_format'));

        $date = DatePicker::make('date');
        $dateTime = DateTimePicker::make('date_time');
        $this->assertFalse($date->isNative());
        $this->assertFalse($dateTime->isNative());
        $this->assertSame('d.m.Y', $date->getDisplayFormat());
        $this->assertSame('d.m.Y H:i', $dateTime->getDisplayFormat());
        $this->assertSame('Europe/Berlin', $dateTime->getTimezone());
    }

    public function test_invalid_saved_timezone_falls_back_safely(): void
    {
        $fallback = config('app.timezone');
        Setting::set('app_timezone', 'Invalid/Timezone', 'regional');

        (new AppServiceProvider(app()))->boot();

        $this->assertSame($fallback, config('app.timezone'));
    }
}
