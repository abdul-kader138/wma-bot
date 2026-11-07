<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\BusinessHours;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessHoursTest extends TestCase
{
    use RefreshDatabase;

    public function test_always_open_when_disabled(): void
    {
        Setting::set('business_hours_enabled', false, 'bot');

        $this->assertTrue(BusinessHours::isOpenNow());
    }

    public function test_open_within_configured_window(): void
    {
        $this->configure();
        $this->travelTo(Carbon::parse('2026-11-11 10:00:00', 'UTC')); // Wednesday

        $this->assertTrue(BusinessHours::isOpenNow());
    }

    public function test_closed_outside_configured_hours(): void
    {
        $this->configure();
        $this->travelTo(Carbon::parse('2026-11-11 22:00:00', 'UTC')); // Wednesday night

        $this->assertFalse(BusinessHours::isOpenNow());
    }

    public function test_closed_on_a_day_not_in_the_schedule(): void
    {
        $this->configure();
        $this->travelTo(Carbon::parse('2026-11-08 12:00:00', 'UTC')); // Sunday

        $this->assertFalse(BusinessHours::isOpenNow());
    }

    private function configure(): void
    {
        Setting::set('business_hours_enabled', true, 'bot');
        Setting::set('business_hours_timezone', 'UTC', 'bot');
        Setting::set('business_hours_start', '09:00', 'bot');
        Setting::set('business_hours_end', '18:00', 'bot');
        Setting::set('business_hours_days', ['mon', 'tue', 'wed', 'thu', 'fri'], 'bot');
    }
}
