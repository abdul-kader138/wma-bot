<x-filament-panels::page>
    <x-filament::section heading="Global emergency stop">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="text-lg font-semibold {{ $enabled ? 'text-success-600' : 'text-danger-600' }}">{{ $enabled ? 'External actions globally enabled' : 'ALL EXTERNAL ACTIONS STOPPED' }}</div>
                <p class="mt-1 text-sm text-gray-500">Per-profile switches and exact approvals are also required. Read-only workflows remain available.</p>
            </div>
            @if ($enabled)
                <x-filament::button color="danger" icon="heroicon-o-stop-circle" wire:click="stopAll" wire:confirm="Stop all Maria external actions immediately?">Emergency stop</x-filament::button>
            @else
                <x-filament::button color="warning" icon="heroicon-o-play" wire:click="enableAll" wire:confirm="Re-enable the global external-action layer? Per-profile controls still apply.">Release global stop</x-filament::button>
            @endif
        </div>
    </x-filament::section>
    <div class="grid gap-4 md:grid-cols-2">
        <x-filament::section heading="Currently executing"><div class="text-3xl font-semibold">{{ $executingCount }}</div><p class="mt-1 text-sm text-gray-500">Inspect provider state before changing ambiguous records.</p></x-filament::section>
        <x-filament::section heading="Pending reconciliations"><div class="text-3xl font-semibold">{{ $pendingReconciliations }}</div><a class="mt-2 inline-block text-sm text-primary-600" href="{{ \App\Filament\Resources\ActionReconciliationResource::getUrl() }}">Open reconciliation queue</a></x-filament::section>
    </div>
</x-filament-panels::page>
