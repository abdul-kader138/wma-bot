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
        <span class="ml-auto text-xs text-gray-400">Scope: {{ $scope }}</span>
    </div>

    @php($t = $metrics['totals'])
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Estimated spend', '$' . number_format($t['cost'], 2), 'tokens: ' . number_format($t['input_tokens']) . ' in / ' . number_format($t['output_tokens']) . ' out'],
            ['Workflow runs', number_format($t['runs']), $t['completed'] . ' completed · ' . $t['failed'] . ' failed'],
            ['Success rate', $t['success_rate'] . '%', 'over the last ' . $metrics['window_days'] . ' days'],
            ['Est. time saved', round($t['est_minutes_saved'] / 60, 1) . ' h', ($t['verified_minutes_saved'] > 0 ? round($t['verified_minutes_saved'] / 60, 1) . ' h verified' : 'estimate — verify in Quality & Corrections')],
        ] as [$label, $value, $sub])
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $value }}</div>
                <div class="mt-1 text-xs text-gray-400">{{ $sub }}</div>
            </div>
        @endforeach
    </div>

    <x-filament::section heading="By workflow">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-gray-500 dark:text-gray-400">
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="py-2 pr-4">Workflow</th>
                        <th class="py-2 pr-4 text-right">Runs</th>
                        <th class="py-2 pr-4 text-right">Success</th>
                        <th class="py-2 pr-4 text-right">Tokens (in/out)</th>
                        <th class="py-2 pr-4 text-right">Cost</th>
                        <th class="py-2 text-right">Time saved</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($metrics['workflows'] as $w)
                        <tr class="border-b border-gray-100 last:border-0 dark:border-gray-800">
                            <td class="py-2 pr-4 font-medium text-gray-950 dark:text-white">{{ $w['workflow'] }}</td>
                            <td class="py-2 pr-4 text-right">{{ $w['runs'] }}</td>
                            <td class="py-2 pr-4 text-right {{ $w['success_rate'] < 90 ? 'text-danger-600' : '' }}">{{ $w['success_rate'] }}%</td>
                            <td class="py-2 pr-4 text-right text-gray-500">{{ number_format($w['input_tokens']) }} / {{ number_format($w['output_tokens']) }}</td>
                            <td class="py-2 pr-4 text-right">${{ number_format($w['cost'], 3) }}</td>
                            <td class="py-2 text-right text-gray-500">{{ round($w['est_minutes_saved'] / 60, 1) }} h</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-gray-500">No workflow runs in this window.</td></tr>
                    @endforelse
                    @php($c = $metrics['chat'])
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="py-2 pr-4 font-medium text-gray-950 dark:text-white">chat ({{ $c['turns'] }} turns, {{ $c['active_conversations'] }} conversations)</td>
                        <td class="py-2 pr-4 text-right">—</td>
                        <td class="py-2 pr-4 text-right">—</td>
                        <td class="py-2 pr-4 text-right text-gray-500">{{ number_format($c['input_tokens']) }} / {{ number_format($c['output_tokens']) }}</td>
                        <td class="py-2 pr-4 text-right">${{ number_format($c['cost'], 3) }}</td>
                        <td class="py-2 text-right text-gray-500">—</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-gray-400">
            Cost uses ${{ $metrics['prices']['input_per_mtok'] }}/M input and ${{ $metrics['prices']['output_per_mtok'] }}/M output tokens (System Settings &rarr; AI). Time saved is an estimate unless verified in Quality &amp; Corrections.
        </p>
    </x-filament::section>

    @if (! empty($metrics['recent_failures']))
        <x-filament::section heading="Recent workflow failures">
            <div class="space-y-3">
                @foreach ($metrics['recent_failures'] as $f)
                    <div class="border-b border-gray-100 pb-3 last:border-0 dark:border-gray-800">
                        <div class="font-medium text-danger-600">{{ $f['workflow'] }} <span class="text-xs text-gray-400">run #{{ $f['id'] }}</span></div>
                        <div class="text-sm text-gray-500">{{ $f['error'] ?: 'no error message recorded' }}</div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
