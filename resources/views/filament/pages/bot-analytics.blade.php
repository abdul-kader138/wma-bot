<x-filament-panels::page>
    <div class="flex items-center gap-3">
        <span class="text-sm text-gray-500 dark:text-gray-400">Window:</span>
        @foreach ([7, 30, 90] as $option)
            <button wire:click="$set('days', {{ $option }})"
                class="rounded-lg px-3 py-1 text-sm ring-1 ring-gray-950/10 dark:ring-white/10
                {{ $days === $option ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-900' }}">
                {{ $option }} days
            </button>
        @endforeach
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Answers given', number_format($total), $total > 0
                ? number_format($faqCount) . ' FAQ · ' . number_format($knowledgeCount) . ' knowledge base · ' . number_format($noneCount) . ' no match'
                : 'No answers logged yet'],
            ['No-match rate', $total > 0 ? round($noneCount / $total * 100, 1) . '%' : '—', 'Claude had nothing to go on'],
            ['Helpful rate', $helpfulRate !== null ? $helpfulRate . '%' : '—', ($up + $down) . ' rated (' . $up . ' up / ' . $down . ' down)'],
            ['Escalations', number_format($escalations), 'asked to talk to a human'],
        ] as [$label, $value, $sub])
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $value }}</div>
                <div class="mt-1 text-xs text-gray-400">{{ $sub }}</div>
            </div>
        @endforeach
    </div>

    <x-filament::section heading="Weak answers — likely knowledge-base gaps">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-gray-500 dark:text-gray-400">
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="py-2 pr-4">When</th>
                        <th class="py-2 pr-4">Question</th>
                        <th class="py-2 pr-4">Source</th>
                        <th class="py-2 pr-4">Rating</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($weak as $event)
                        <tr class="border-b border-gray-100 last:border-0 dark:border-gray-800">
                            <td class="py-2 pr-4 whitespace-nowrap text-gray-500">{{ $event->created_at->diffForHumans() }}</td>
                            <td class="py-2 pr-4 max-w-md truncate" title="{{ $event->question }}">{{ $event->question }}</td>
                            <td class="py-2 pr-4">
                                <span class="rounded-full px-2 py-0.5 text-xs
                                    {{ $event->match_source === 'none' ? 'bg-danger-50 text-danger-600 dark:bg-danger-500/10' : 'bg-warning-50 text-warning-600 dark:bg-warning-500/10' }}">
                                    {{ $event->match_source }}
                                </span>
                            </td>
                            <td class="py-2 pr-4 text-gray-500">
                                @if ($event->rating === 1) 👍
                                @elseif ($event->rating === -1) 👎
                                @else —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-gray-500">No weak answers in this window.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
