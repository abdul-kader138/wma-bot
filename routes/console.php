<?php

use App\Jobs\Maria\GenerateEveningReview;
use App\Jobs\Maria\GenerateMorningBrief;
use App\Jobs\Maria\PrepareUpcomingMeetings;
use App\Jobs\Maria\TriageGoogleInbox;
use App\Models\AssistantProfile;
use App\Models\ConnectorAccount;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    AssistantProfile::query()->where('is_active', true)->whereNotNull('morning_brief_at')->each(function (AssistantProfile $profile) {
        $local = now($profile->timezone);
        if (substr((string) $profile->morning_brief_at, 0, 5) === $local->format('H:i')) {
            GenerateMorningBrief::dispatch($profile->id, $local->toDateString());
        }
    });
})->everyMinute()->name('maria-morning-briefs')->withoutOverlapping();

Schedule::call(function () {
    AssistantProfile::query()->where('is_active', true)->whereNotNull('evening_review_at')->each(function (AssistantProfile $profile) {
        $local = now($profile->timezone);
        if (substr((string) $profile->evening_review_at, 0, 5) === $local->format('H:i')) {
            GenerateEveningReview::dispatch($profile->id, $local->toDateString());
        }
    });
})->everyMinute()->name('maria-evening-reviews')->withoutOverlapping();

Schedule::call(function () {
    ConnectorAccount::query()->where('provider', 'google')->where('status', 'active')
        ->whereHas('user.assistantProfile', fn ($query) => $query->where('is_active', true))
        ->with('user.assistantProfile')->each(function (ConnectorAccount $connector) {
            $profile = $connector->user->assistantProfile;
            $local = now($profile->timezone);
            $start = $profile->working_hours_start ? substr((string) $profile->working_hours_start, 0, 5) : '08:00';
            $end = $profile->working_hours_end ? substr((string) $profile->working_hours_end, 0, 5) : '18:00';
            if ($local->format('H:i') >= $start && $local->format('H:i') <= $end) {
                TriageGoogleInbox::dispatch($connector->id, $local->format('Y-m-d-H-i'));
            }
        });
})->everyThirtyMinutes()->name('maria-email-triage')->withoutOverlapping();

Schedule::call(function () {
    ConnectorAccount::query()->where('provider', 'google')->where('status', 'active')
        ->whereHas('user.assistantProfile', fn ($query) => $query->where('is_active', true))
        ->with('user.assistantProfile')->each(function (ConnectorAccount $connector) {
            $profile = $connector->user->assistantProfile;
            PrepareUpcomingMeetings::dispatch($connector->id, $profile->id, now($profile->timezone)->format('Y-m-d-H'));
        });
})->hourly()->name('maria-meeting-preparation')->withoutOverlapping();
