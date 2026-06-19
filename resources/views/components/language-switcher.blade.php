@php
    $flags = [
        'en' => '🇬🇧',
        'it' => '🇮🇹',
        'bn' => '🇧🇩',
    ];

    $current   = app()->getLocale();
    $available = config('locales.available', []);
@endphp

<x-filament::dropdown placement="bottom-end" teleport>

    <x-slot name="trigger">
        <button
            type="button"
            class="flex items-center gap-1.5 rounded-lg px-2 py-1.5
                   text-gray-500 hover:text-gray-700 hover:bg-gray-100
                   dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-white/5
                   transition focus:outline-none"
        >
            <span class="text-base leading-none">{{ $flags[$current] ?? '🌐' }}</span>
            <span class="text-xs font-semibold uppercase tracking-wide">{{ $current }}</span>
            <x-filament::icon
                icon="heroicon-m-chevron-down"
                class="h-3.5 w-3.5 opacity-60"
            />
        </button>
    </x-slot>

    <x-filament::dropdown.list>
        @foreach ($available as $code => $label)
            <x-filament::dropdown.list.item
                tag="a"
                :href="route('filament.admin.set-locale', $code)"
                :color="$code === $current ? 'primary' : 'gray'"
                :icon="$code === $current ? 'heroicon-m-check' : null"
                icon-position="after"
            >
                <span class="flex items-center gap-2">
                    <span class="text-base leading-none">{{ $flags[$code] ?? '🌐' }}</span>
                    <span>{{ $label }}</span>
                </span>
            </x-filament::dropdown.list.item>
        @endforeach
    </x-filament::dropdown.list>

</x-filament::dropdown>
