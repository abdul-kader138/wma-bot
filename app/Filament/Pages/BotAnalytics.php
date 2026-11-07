<?php

namespace App\Filament\Pages;

use App\Models\BotAnswerEvent;
use App\Models\Conversation;
use App\Models\Setting;
use Filament\Pages\Page;

/**
 * Reads bot_answer_events (logged by HandleIncomingMessage::logAnswerEvent()) to show
 * staff where the bot is actually landing: FAQ vs knowledge-base vs "had nothing to go
 * on", how often customers rate a reply helpful, and a concrete list of real questions
 * that went unanswered or got a thumbs-down — literally what to add to the knowledge
 * base next.
 */
class BotAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.bot-analytics';

    public int $days = 30;

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.bot_analytics');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $superAdminName = (string) config('filament-shield.super_admin.name', 'super_admin');

        if ((bool) config('filament-shield.super_admin.enabled', true) && $user->hasRole($superAdminName)) {
            return true;
        }

        return $user->can('page_BotAnalytics');
    }

    public function getTitle(): string
    {
        return __('admin.nav.bot_analytics');
    }

    public function getSubheading(): string
    {
        return 'Where bot replies are landing, how customers rate them, and the real questions that went unanswered.';
    }

    public function getViewData(): array
    {
        $since = now()->subDays($this->days);

        $bySource = BotAnswerEvent::where('created_at', '>=', $since)
            ->selectRaw('match_source, count(*) as c')
            ->groupBy('match_source')
            ->pluck('c', 'match_source');

        $total = (int) $bySource->sum();

        $up = BotAnswerEvent::where('created_at', '>=', $since)->where('rating', 1)->count();
        $down = BotAnswerEvent::where('created_at', '>=', $since)->where('rating', -1)->count();

        $threshold = (float) (Setting::get('knowledge_max_distance') ?? 0.4);

        $weak = BotAnswerEvent::where('created_at', '>=', $since)
            ->where(function ($query) use ($threshold) {
                $query->where('match_source', 'none')
                    ->orWhere('rating', -1)
                    // Borderline knowledge-base matches: close to the cutoff rather than
                    // a confident hit, worth a staff look even though it technically passed.
                    ->orWhere(fn ($q) => $q->where('match_source', 'knowledge')->where('top_distance', '>=', $threshold * 0.75));
            })
            ->latest()
            ->limit(50)
            ->get();

        return [
            'days' => $this->days,
            'total' => $total,
            'faqCount' => (int) ($bySource['faq'] ?? 0),
            'knowledgeCount' => (int) ($bySource['knowledge'] ?? 0),
            'noneCount' => (int) ($bySource['none'] ?? 0),
            'up' => $up,
            'down' => $down,
            'helpfulRate' => ($up + $down) > 0 ? round($up / ($up + $down) * 100, 1) : null,
            'escalations' => Conversation::where('escalated_at', '>=', $since)->count(),
            'weak' => $weak,
        ];
    }
}
