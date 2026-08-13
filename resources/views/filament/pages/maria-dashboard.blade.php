<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <a @if ($stat['url']) href="{{ $stat['url'] }}" @endif
               class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $stat['value'] }}</div>
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-filament::section heading="Next tasks">
            <div class="space-y-3">
                @forelse ($dueTasks as $task)
                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 pb-3 last:border-0 dark:border-gray-700">
                        <div>
                            <div class="font-medium text-gray-950 dark:text-white">{{ $task->task }}</div>
                            <div class="text-sm text-gray-500">Owner: {{ $task->owner_name }}</div>
                        </div>
                        <div class="whitespace-nowrap text-sm {{ $task->due_at->isPast() ? 'text-danger-600' : 'text-gray-500' }}">
                            {{ $task->due_at->timezone(auth()->user()->assistantProfile?->timezone ?? config('app.timezone'))->format('d M H:i') }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No dated tasks are currently open.</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section heading="Approval queue">
            <div class="space-y-3">
                @forelse ($pendingApprovals as $approval)
                    <a href="{{ \App\Filament\Resources\ApprovalResource::getUrl('view', ['record' => $approval]) }}"
                       class="block border-b border-gray-200 pb-3 last:border-0 dark:border-gray-700">
                        <div class="font-medium text-gray-950 dark:text-white">{{ $approval->proposed_action }}</div>
                        <div class="text-sm text-gray-500">{{ ucfirst($approval->risk_level) }} risk · expires {{ $approval->expires_at->diffForHumans() }}</div>
                    </a>
                @empty
                    <p class="text-sm text-gray-500">No decisions are waiting.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>

    @if ($latestBrief)
        <x-filament::section heading="Latest Morning Command Brief">
            <div class="grid gap-4 lg:grid-cols-3">
                @foreach (array_slice($latestBrief->content['outcomes'] ?? [], 0, 3) as $outcome)
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                        <div class="font-semibold text-gray-950 dark:text-white">{{ $outcome['title'] ?? 'Outcome' }}</div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $outcome['reason'] ?? '' }}</p>
                        <p class="mt-3 text-sm font-medium text-primary-600">{{ $outcome['next_action'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
            @if (!empty($latestBrief->content['source_gaps']))
                <p class="mt-4 text-sm text-warning-600">Sources not checked: {{ implode(', ', $latestBrief->content['source_gaps']) }}</p>
            @endif
        </x-filament::section>
    @endif

    @if ($latestEveningReview)
        <x-filament::section heading="Latest Evening Review">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    'Completed' => $latestEveningReview->content['completed'] ?? [],
                    'Awaiting approval' => $latestEveningReview->content['awaiting_approval'] ?? [],
                    'Waiting on others' => $latestEveningReview->content['waiting_on_others'] ?? [],
                    "Tomorrow's top three" => $latestEveningReview->content['tomorrow_top_three'] ?? [],
                ] as $heading => $items)
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                        <div class="font-semibold text-gray-950 dark:text-white">{{ $heading }}</div>
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ count($items) }} item(s)</div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    <x-filament::section heading="Daily Five Relationships">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            @forelse ($dailyFive as $recommendation)
                <a href="{{ \App\Filament\Resources\RelationshipRecommendationResource::getUrl('view', ['record' => $recommendation]) }}"
                   class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                    <div class="font-medium text-gray-950 dark:text-white">{{ $recommendation->contact->full_name }}</div>
                    <div class="mt-1 text-sm text-gray-500">{{ $recommendation->contact->organization ?: 'Independent' }} · {{ ucfirst($recommendation->recommended_stage) }}</div>
                    <div class="mt-2 text-xs font-medium text-primary-600">{{ ucfirst($recommendation->status) }}</div>
                </a>
            @empty
                <p class="text-sm text-gray-500">No relationship recommendations for today.</p>
            @endforelse
        </div>
    </x-filament::section>

    <x-filament::section heading="Recent workflow runs">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            @forelse ($recentWorkflows as $run)
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                    <div class="font-medium text-gray-950 dark:text-white">{{ str($run->workflow_type)->headline() }}</div>
                    <div class="mt-1 text-sm text-gray-500">{{ ucfirst($run->status) }}</div>
                </div>
            @empty
                <p class="text-sm text-gray-500">No Maria workflows have run yet.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-panels::page>
