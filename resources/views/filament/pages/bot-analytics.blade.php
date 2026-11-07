<x-filament-panels::page>
    <div class="flex items-center gap-2">
        <span class="text-sm text-gray-500 dark:text-gray-400">Window:</span>
        @foreach ([7, 30, 90] as $option)
            <x-filament::button
                size="sm"
                :color="$days === $option ? 'primary' : 'gray'"
                :outlined="$days !== $option"
                wire:click="$set('days', {{ $option }})"
                wire:loading.attr="disabled"
                wire:target="days"
            >
                {{ $option }} days
            </x-filament::button>
        @endforeach
    </div>

    @php
        $iconColorClasses = [
            'primary' => 'text-primary-600 dark:text-primary-400',
            'danger' => 'text-danger-600 dark:text-danger-400',
            'success' => 'text-success-600 dark:text-success-400',
            'warning' => 'text-warning-600 dark:text-warning-400',
        ];
    @endphp

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['icon' => 'heroicon-o-chat-bubble-left-right', 'color' => 'primary', 'label' => 'Answers given', 'value' => number_format($total), 'sub' => $total > 0
                ? number_format($faqCount) . ' FAQ · ' . number_format($knowledgeCount) . ' knowledge base · ' . number_format($noneCount) . ' no match'
                : 'No answers logged yet'],
            ['icon' => 'heroicon-o-exclamation-triangle', 'color' => 'danger', 'label' => 'No-match rate', 'value' => $total > 0 ? round($noneCount / $total * 100, 1) . '%' : '—', 'sub' => 'Claude had nothing to go on'],
            ['icon' => 'heroicon-o-hand-thumb-up', 'color' => 'success', 'label' => 'Helpful rate', 'value' => $helpfulRate !== null ? $helpfulRate . '%' : '—', 'sub' => ($up + $down) . ' rated (' . $up . ' up / ' . $down . ' down)'],
            ['icon' => 'heroicon-o-hand-raised', 'color' => 'warning', 'label' => 'Escalations', 'value' => number_format($escalations), 'sub' => 'asked to talk to a human'],
        ] as $card)
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-2">
                    <x-filament::icon :icon="$card['icon']" class="h-5 w-5 {{ $iconColorClasses[$card['color']] }}" />
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</span>
                </div>
                <div class="mt-2 text-3xl font-semibold tabular-nums text-gray-950 dark:text-white">{{ $card['value'] }}</div>
                <div class="mt-1 text-xs text-gray-400">{{ $card['sub'] }}</div>
            </div>
        @endforeach
    </div>

    <x-filament::section icon="heroicon-o-magnifying-glass" heading="Weak answers" description="Likely knowledge-base gaps — no match, a thumbs-down, or a knowledge-base hit that barely cleared the cutoff.">
        @if ($weak->isEmpty())
            <div class="flex flex-col items-center gap-2 py-10 text-center">
                <x-filament::icon icon="heroicon-o-check-circle" class="h-8 w-8 text-success-500" />
                <p class="text-sm text-gray-500 dark:text-gray-400">No weak answers in this window — the knowledge base is covering what customers actually ask.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="py-2 pr-4">When</th>
                            <th class="py-2 pr-4">Question</th>
                            <th class="py-2 pr-4">Contact</th>
                            <th class="py-2 pr-4">Source</th>
                            <th class="py-2 pr-4">Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($weak as $event)
                            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-white/5 dark:hover:bg-white/5">
                                <td class="py-2 pr-4 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $event->created_at->diffForHumans() }}</td>
                                <td class="py-2 pr-4 max-w-md truncate text-gray-700 dark:text-gray-300" title="{{ $event->question }}">{{ $event->question }}</td>
                                <td class="py-2 pr-4 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $event->wa_phone }}</td>
                                <td class="py-2 pr-4">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $event->match_source === 'none' ? 'bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400' : 'bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400' }}">
                                        {{ $event->match_source }}
                                    </span>
                                </td>
                                <td class="py-2 pr-4">
                                    @if ($event->rating === 1)
                                        <x-filament::icon icon="heroicon-o-hand-thumb-up" class="h-4 w-4 text-success-600 dark:text-success-400" />
                                    @elseif ($event->rating === -1)
                                        <x-filament::icon icon="heroicon-o-hand-thumb-down" class="h-4 w-4 text-danger-600 dark:text-danger-400" />
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
