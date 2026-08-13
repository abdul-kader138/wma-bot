<?php

use App\Jobs\Maria\GenerateBookPortfolioReview;
use App\Jobs\Maria\GenerateDailyFive;
use App\Jobs\Maria\GenerateEveningReview;
use App\Jobs\Maria\GenerateMorningBrief;
use App\Jobs\Maria\MonitorDeadlines;
use App\Jobs\Maria\PrepareUpcomingMeetings;
use App\Jobs\Maria\ReviewAgverseOpportunities;
use App\Jobs\Maria\TriageGoogleInbox;
use App\Models\AssistantProfile;
use App\Models\ConnectorAccount;
use Carbon\Carbon;
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

Schedule::call(function () {
    AssistantProfile::where('is_active', true)->each(function (AssistantProfile $profile) {
        MonitorDeadlines::dispatch($profile->id, now($profile->timezone)->format('Y-m-d-H'));
    });
})->hourly()->name('maria-deadline-monitor')->withoutOverlapping();

Schedule::call(function () {
    AssistantProfile::where('is_active', true)->each(function (AssistantProfile $profile) {
        $local = now($profile->timezone);
        $briefAt = $profile->morning_brief_at ? substr((string) $profile->morning_brief_at, 0, 5) : '07:30';
        $dailyFiveAt = Carbon::createFromFormat('H:i', $briefAt, $profile->timezone)->addMinutes(30)->format('H:i');
        if ($local->isWeekday() && $local->format('H:i') === $dailyFiveAt && in_array('daily_five', $profile->enabled_workflows ?? [], true)) {
            GenerateDailyFive::dispatch($profile->id, $local->toDateString());
        }
    });
})->everyMinute()->name('maria-daily-five')->withoutOverlapping();

Schedule::call(function () {
    AssistantProfile::where('is_active', true)->each(function (AssistantProfile $profile) {
        $local = now($profile->timezone);
        $briefAt = $profile->morning_brief_at ? substr((string) $profile->morning_brief_at, 0, 5) : '07:30';
        $reviewAt = Carbon\Carbon::createFromFormat('H:i', $briefAt, $profile->timezone)->addHour()->format('H:i');
        if ($local->isMonday() && $local->format('H:i') === $reviewAt && in_array('book_portfolio_review', $profile->enabled_workflows ?? [], true)) {
            GenerateBookPortfolioReview::dispatch($profile->id, $local->startOfWeek()->toDateString());
        }
    });
})->everyMinute()->name('maria-book-portfolio-review')->withoutOverlapping();

Schedule::call(function () {
    AssistantProfile::where('is_active', true)->each(function (AssistantProfile $profile) {
        $local = now($profile->timezone);
        $briefAt = $profile->morning_brief_at ? substr((string) $profile->morning_brief_at, 0, 5) : '07:30';
        $reviewAt = Carbon::createFromFormat('H:i', $briefAt, $profile->timezone)->addMinutes(90)->format('H:i');
        if ($local->isThursday() && $local->format('H:i') === $reviewAt && in_array('agverse_opportunity_review', $profile->enabled_workflows ?? [], true)) {
            ReviewAgverseOpportunities::dispatch($profile->id, $local->toDateString());
        }
    });
})->everyMinute()->name('maria-agverse-opportunity-review')->withoutOverlapping();
