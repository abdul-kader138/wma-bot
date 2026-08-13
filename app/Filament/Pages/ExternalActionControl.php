<?php

namespace App\Filament\Pages;

use App\Models\ActionReconciliation;
use App\Models\AssistantAction;
use App\Models\Setting;
use App\Services\AuditLogger;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ExternalActionControl extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-stop-circle';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?string $navigationLabel = 'External Action Control';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.external-action-control';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function stopAll(): void
    {
        $this->setEnabled(false, 'emergency_stop_activated');
        Notification::make()->title('All Maria external actions stopped')->danger()->send();
    }

    public function enableAll(): void
    {
        $this->setEnabled(true, 'emergency_stop_released');
        Notification::make()->title('Global external actions enabled')->success()->send();
    }

    public function getViewData(): array
    {
        return ['enabled' => filter_var(Setting::get('maria_external_actions_enabled', true), FILTER_VALIDATE_BOOLEAN), 'executingCount' => AssistantAction::where('status', 'executing')->count(), 'pendingReconciliations' => ActionReconciliation::where('status', 'pending')->count()];
    }

    private function setEnabled(bool $enabled, string $auditAction): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        Setting::set('maria_external_actions_enabled', $enabled, 'maria_safety');
        app(AuditLogger::class)->recordContext('maria_safety', $auditAction, auth()->id(), metadata: ['external_actions_enabled' => $enabled]);
    }
}
