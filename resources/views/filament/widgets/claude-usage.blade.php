<x-filament-widgets::widget>
    @php
        $globalCap   = $this->getGlobalCap();
        $globalCount = $this->getGlobalCount();
        $phoneCap    = $this->getPhoneCap();
        $phoneUsage  = $this->getPhoneUsage();

        $globalPct = $globalCap > 0 ? min(100, (int) round($globalCount / $globalCap * 100)) : 0;
        $globalColor = match (true) {
            $globalCap <= 0 => 'gray',
            $globalPct >= 100 => 'danger',
            $globalPct >= 80 => 'warning',
            default => 'success',
        };
    @endphp

    <x-filament::section>
        <x-slot name="heading">
            Claude Messages Today
        </x-slot>
        <x-slot name="description">
            Live counts behind the daily caps in System Settings &rarr; Claude AI. Resets at midnight.
        </x-slot>

        <div class="flex flex-col gap-4">
            {{-- Global circuit breaker --}}
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-950 dark:text-white">All customers (global cap)</span>
                        <span class="text-gray-500 dark:text-gray-400">
                            @if($globalCap > 0)
                                {{ $globalCount }} / {{ $globalCap }}
                            @else
                                {{ $globalCount }} sent &middot; cap disabled
                            @endif
                        </span>
                    </div>

                    @if($globalCap > 0)
                        <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                            <div
                                @class([
                                    'h-full rounded-full transition-all',
                                    'bg-success-500' => $globalColor === 'success',
                                    'bg-warning-500' => $globalColor === 'warning',
                                    'bg-danger-500'  => $globalColor === 'danger',
                                ])
                                style="width: {{ $globalPct }}%"
                            ></div>
                        </div>
                    @endif
                </div>

                <x-filament::badge :color="$globalColor">
                    @if($globalCap <= 0)
                        Not set
                    @elseif($globalPct >= 100)
                        Tripped
                    @else
                        {{ $globalPct }}%
                    @endif
                </x-filament::badge>
            </div>

            {{-- Per-phone table --}}
            <div>
                <p class="mb-2 text-sm font-medium text-gray-950 dark:text-white">
                    Busiest phone numbers today
                    <span class="font-normal text-gray-500 dark:text-gray-400">(cap: {{ $phoneCap > 0 ? $phoneCap.' messages/day' : 'disabled' }})</span>
                </p>

                @if(empty($phoneUsage))
                    <p class="text-sm text-gray-500 dark:text-gray-400">No Claude messages sent yet today.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                                    <th class="py-1.5 pr-3 font-medium">Phone</th>
                                    <th class="py-1.5 pr-3 font-medium">Account</th>
                                    <th class="py-1.5 pr-3 font-medium text-right">Messages</th>
                                    <th class="py-1.5 font-medium text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @foreach($phoneUsage as $row)
                                    @php
                                        $overCap = $phoneCap > 0 && $row['count'] >= $phoneCap;
                                    @endphp
                                    <tr>
                                        <td class="py-1.5 pr-3 text-gray-950 dark:text-white">{{ $row['phone'] }}</td>
                                        <td class="py-1.5 pr-3 text-gray-500 dark:text-gray-400">{{ $row['account_name'] ?? '—' }}</td>
                                        <td class="py-1.5 pr-3 text-right text-gray-950 dark:text-white">{{ $row['count'] }}</td>
                                        <td class="py-1.5 text-right">
                                            <x-filament::badge :color="$overCap ? 'danger' : 'gray'" size="sm">
                                                {{ $overCap ? 'Blocked' : 'OK' }}
                                            </x-filament::badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
