<x-filament-panels::page>
    <form wire:submit="run">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Test
            </x-filament::button>
        </div>
    </form>

    @if ($result)
        @php($faq = $result['faq'])
        <x-filament::section heading="FAQ matcher">
            <p class="text-xs text-gray-500">Threshold: {{ $faq['threshold'] }} · match type: {{ $faq['match_type'] ?? 'none' }}</p>

            @if ($faq['matched'])
                <div class="mt-2 rounded-lg bg-success-50 p-3 text-sm dark:bg-success-500/10">
                    <strong>Matched:</strong> {{ $faq['matched']->questionFor(app()->getLocale()) }}
                    <div class="mt-1 text-gray-600 dark:text-gray-300">{{ $faq['matched']->answerFor(app()->getLocale()) }}</div>
                </div>
            @else
                <p class="mt-2 text-sm text-gray-500">No FAQ matched.</p>
            @endif

            @if (! empty($faq['candidates']))
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-gray-500 dark:text-gray-400">
                            <tr><th class="py-1 pr-4">Question</th><th class="py-1 pr-4 text-right">Score</th></tr>
                        </thead>
                        <tbody>
                            @foreach (array_slice($faq['candidates'], 0, 10) as $c)
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="py-1 pr-4">{{ $c['faq']->questionFor(app()->getLocale()) }}</td>
                                    <td class="py-1 pr-4 text-right {{ $c['score'] >= $faq['threshold'] ? 'text-success-600' : 'text-gray-400' }}">
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
        <x-filament::section heading="Knowledge base">
            @if ($result['knowledge_error'])
                <p class="text-sm text-danger-600">Retrieval failed: {{ $result['knowledge_error'] }}</p>
            @else
                <p class="text-xs text-gray-500">Max distance (cutoff): {{ $kb['max_distance'] }}</p>

                @if (! empty($kb['candidates']))
                    <div class="mt-3 space-y-2">
                        @foreach ($kb['candidates'] as $c)
                            <div class="rounded-lg p-3 text-sm {{ $c['passes'] ? 'bg-success-50 dark:bg-success-500/10' : 'bg-gray-50 dark:bg-gray-800' }}">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium {{ $c['passes'] ? 'text-success-700 dark:text-success-400' : 'text-gray-500' }}">
                                        distance {{ number_format($c['distance'], 3) }}
                                        {{ $c['passes'] ? '✓ used' : '✗ below cutoff' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-gray-600 dark:text-gray-300">{{ \Illuminate\Support\Str::limit($c['chunk']->content, 300) }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-2 text-sm text-gray-500">No knowledge base candidates found.</p>
                @endif
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
