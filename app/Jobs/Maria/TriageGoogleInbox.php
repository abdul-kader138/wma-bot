<?php

namespace App\Jobs\Maria;

use App\Models\ConnectorAccount;
use App\Services\Maria\EmailTriageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TriageGoogleInbox implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public int $uniqueFor = 1500;

    public function __construct(public int $connectorId, public string $window) {}

    public function uniqueId(): string
    {
        return "{$this->connectorId}:{$this->window}";
    }

    public function handle(EmailTriageService $triage): void
    {
        $triage->triage(ConnectorAccount::findOrFail($this->connectorId));
    }
}
