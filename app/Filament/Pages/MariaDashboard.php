<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ApprovalResource;
use App\Filament\Resources\MariaProjectResource;
use App\Filament\Resources\MariaTaskResource;
use App\Filament\Resources\RelationshipRecommendationResource;
use App\Models\Approval;
use App\Models\AssistantBrief;
use App\Models\MariaProject;
use App\Models\MariaTask;
use App\Models\RelationshipRecommendation;
use App\Models\WorkflowRun;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class MariaDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'Command Center';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.maria-dashboard';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) $user && ($user->hasAnyRole(['super_admin', 'panel_user']) || $user->getAllPermissions()->isNotEmpty());
    }

    public function getViewData(): array
    {
        $projects = $this->owned(MariaProject::query());
        $tasks = $this->owned(MariaTask::query());
        $approvals = $this->owned(Approval::query());
        $workflows = $this->owned(WorkflowRun::query());
        $relationships = $this->owned(RelationshipRecommendation::query());

        return [
            'stats' => [
                ['label' => 'Active projects', 'value' => (clone $projects)->whereIn('stage', ['defined', 'active', 'waiting', 'review'])->count(), 'url' => MariaProjectResource::getUrl()],
                ['label' => 'Open tasks', 'value' => (clone $tasks)->whereNotIn('status', ['completed'])->count(), 'url' => MariaTaskResource::getUrl()],
                ['label' => 'Pending approvals', 'value' => (clone $approvals)->where('decision', 'pending')->where('expires_at', '>', now())->count(), 'url' => ApprovalResource::getUrl()],
                ['label' => 'Workflow failures', 'value' => (clone $workflows)->where('status', 'failed')->where('created_at', '>=', now()->subWeek())->count(), 'url' => null],
                ['label' => 'Daily Five pending', 'value' => (clone $relationships)->where('status', 'pending')->count(), 'url' => RelationshipRecommendationResource::getUrl()],
            ],
            'dueTasks' => (clone $tasks)->whereNotIn('status', ['completed'])->whereNotNull('due_at')->orderBy('due_at')->limit(7)->get(),
            'pendingApprovals' => (clone $approvals)->where('decision', 'pending')->where('expires_at', '>', now())->orderBy('expires_at')->limit(5)->get(),
            'recentWorkflows' => (clone $workflows)->latest()->limit(5)->get(),
            'latestBrief' => $this->owned(AssistantBrief::query())->where('type', 'morning')->latest('brief_date')->first(),
            'latestEveningReview' => $this->owned(AssistantBrief::query())->where('type', 'evening')->latest('brief_date')->first(),
            'latestBookReview' => $this->owned(AssistantBrief::query())->where('type', 'book_portfolio')->latest('brief_date')->first(),
            'dailyFive' => (clone $relationships)->with('contact')->whereDate('recommendation_date', now()->toDateString())->orderByDesc('score')->limit(5)->get(),
        ];
    }

    private function owned(Builder $query): Builder
    {
        return auth()->user()->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }
}
