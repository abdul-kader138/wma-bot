<x-filament-panels::page>
    <x-filament::section heading="30-day operating summary">
        <p class="mb-4 text-sm text-gray-500">{{ $report['period']['from'] }} to {{ $report['period']['to'] }}. Estimated and verified savings are deliberately reported separately.</p>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                'Completed workflows' => $report['operations']['completed_workflows'],
                'Failed workflows' => $report['operations']['failed_workflows'],
                'Estimated minutes saved' => $report['operations']['estimated_minutes_saved'],
                'Verified minutes saved' => $report['operations']['verified_minutes_saved'],
                'Verified workflow runs' => $report['operations']['verified_runs'],
                'External actions completed' => $report['operations']['completed_external_actions'],
                'Corrections' => $report['operations']['corrections'],
                'Safety incidents' => $report['operations']['safety_incidents'],
            ] as $label => $value)
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/5"><div class="text-sm text-gray-500">{{ $label }}</div><div class="mt-2 text-2xl font-semibold">{{ $value }}</div></div>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section heading="Client acceptance thresholds">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b dark:border-gray-700"><th class="p-3">Metric</th><th class="p-3">Result</th><th class="p-3">Target</th><th class="p-3">Status</th></tr></thead>
                <tbody>
                @foreach ($report['metrics'] as $metric)
                    <tr class="border-b last:border-0 dark:border-gray-800">
                        <td class="p-3 font-medium">{{ $metric['name'] }}</td>
                        <td class="p-3">
                            @if ($metric['status'] === 'not_measured')
                                <span class="text-gray-500">Not measured</span><div class="text-xs text-gray-400">{{ $metric['reason'] }}</div>
                            @else
                                {{ $metric['value'] }}{{ $metric['unit'] === 'percent' ? '%' : '' }}
                                @if ($metric['denominator']) <span class="text-xs text-gray-400">({{ $metric['numerator'] }}/{{ $metric['denominator'] }})</span> @endif
                            @endif
                        </td>
                        <td class="p-3">{{ $metric['target'] === null ? '—' : ($metric['unit'] === 'percent' ? '≥ '.$metric['target'].'%' : $metric['target']) }}</td>
                        <td class="p-3"><span class="rounded-full px-2 py-1 text-xs font-medium {{ $metric['status'] === 'pass' ? 'bg-success-100 text-success-700' : ($metric['status'] === 'fail' ? 'bg-danger-100 text-danger-700' : 'bg-gray-100 text-gray-600') }}">{{ str($metric['status'])->headline() }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
