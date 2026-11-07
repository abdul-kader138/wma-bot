<x-filament-panels::page>
    <x-filament::section icon="heroicon-o-beaker" heading="Test a question">
        <form wire:submit="run" class="space-y-4">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit" icon="heroicon-o-play">
                    Run test
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    @if ($result)
        <div class="grid gap-6 lg:grid-cols-2">
            @php($faq = $result['faq'])
            <x-filament::section icon="heroicon-o-question-mark-circle" heading="FAQ matcher">
                <x-slot name="description">
                    Threshold {{ number_format($faq['threshold'], 2) }} ·
                    {{ $faq['match_type'] ? ucfirst($faq['match_type']).' match' : 'No match' }}
                </x-slot>

                @if ($faq['matched'])
                    <div class="flex gap-3 rounded-lg bg-success-50 p-3 dark:bg-success-500/10">
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 flex-shrink-0 text-success-600 dark:text-success-400" />
                        <div class="text-sm">
                            <div class="font-medium text-success-800 dark:text-success-300">{{ $faq['matched']->questionFor(app()->getLocale()) }}</div>
                            <p class="mt-1 text-gray-700 dark:text-gray-300">{{ $faq['matched']->answerFor(app()->getLocale()) }}</p>
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-500 dark:bg-white/5 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-o-x-circle" class="h-5 w-5 flex-shrink-0" />
                        No FAQ cleared the threshold.
                    </div>
                @endif

                @if (! empty($faq['candidates']))
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                                    <th class="py-2 pr-4">Candidate question</th>
                                    <th class="py-2 pr-4 text-right">Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (array_slice($faq['candidates'], 0, 10) as $c)
                                    <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-white/5 dark:hover:bg-white/5">
                                        <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ $c['faq']->questionFor(app()->getLocale()) }}</td>
                                        <td class="py-2 pr-4 text-right font-medium tabular-nums {{ $c['score'] >= $faq['threshold'] ? 'text-success-600 dark:text-success-400' : 'text-gray-400' }}">
                                            {{ number_format($c['score'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>

            @php($kb = $result['knowledge'])
            <x-filament::section icon="heroicon-o-book-open" heading="Knowledge base">
                <x-slot name="description">
                    Cutoff distance {{ number_format($kb['max_distance'] ?? 0, 2) }}
                </x-slot>

                @if ($result['knowledge_error'])
                    <div class="flex items-center gap-3 rounded-lg bg-danger-50 p-3 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 flex-shrink-0" />
                        Retrieval failed: {{ $result['knowledge_error'] }}
                    </div>
                @elseif (! empty($kb['candidates']))
                    <div class="space-y-2">
                        @foreach ($kb['candidates'] as $c)
                            <div class="flex gap-3 rounded-lg p-3 text-sm {{ $c['passes'] ? 'bg-success-50 dark:bg-success-500/10' : 'bg-gray-50 dark:bg-white/5' }}">
                                <x-filament::icon
                                    :icon="$c['passes'] ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'"
                                    class="h-5 w-5 flex-shrink-0 {{ $c['passes'] ? 'text-success-600 dark:text-success-400' : 'text-gray-400' }}"
                                />
                                <div>
                                    <div class="font-medium tabular-nums {{ $c['passes'] ? 'text-success-800 dark:text-success-300' : 'text-gray-500 dark:text-gray-400' }}">
                                        distance {{ number_format($c['distance'], 3) }}
                                        <span class="font-normal">{{ $c['passes'] ? '— used in the reply' : '— below cutoff' }}</span>
                                    </div>
                                    <p class="mt-1 text-gray-600 dark:text-gray-300">{{ \Illuminate\Support\Str::limit($c['chunk']->content, 300) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-center gap-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-500 dark:bg-white/5 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-o-x-circle" class="h-5 w-5 flex-shrink-0" />
                        No knowledge base candidates found.
                    </div>
                @endif
            </x-filament::section>
        </div>
    @else
        <div class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-gray-300 p-12 text-center dark:border-white/10">
            <x-filament::icon icon="heroicon-o-beaker" class="h-8 w-8 text-gray-400" />
            <p class="text-sm text-gray-500 dark:text-gray-400">Type a question above and run the test to see what the bot would do with it.</p>
        </div>
    @endif
</x-filament-panels::page>
