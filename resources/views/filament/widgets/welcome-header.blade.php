<x-filament-widgets::widget>
@php
    $user       = $this->getUser();
    $greeting   = $this->getGreeting();
    $roles      = $user?->getRoleNames()->map(fn($r) => str($r)->replace('_',' ')->title())->join(', ') ?: '—';
    $avatar     = $user?->getFilamentAvatarUrl();
    $newCount   = \App\Models\ServiceRequest::where('status', 'new')->count();
    $activeConv = \App\Models\Conversation::whereIn('step', ['IN_SERVICE','AWAIT_LANG','AWAIT_SERVICE'])->count();
    $todayReqs  = \App\Models\ServiceRequest::whereDate('created_at', today())->count();
    $accent     = 'rgb(245 158 11)';
@endphp

<div style="
    background: linear-gradient(135deg, rgb(20 15 8) 0%, rgb(44 28 8) 45%, rgb(72 40 8) 100%);
    border: 1px solid rgba(245,158,11,.18);
    border-radius: 1rem;
    padding: 2rem 2.5rem;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 2rem;
">
    {{-- Glow blobs --}}
    <div style="position:absolute;top:-4rem;right:-4rem;width:22rem;height:22rem;border-radius:9999px;background:rgb(245 158 11);opacity:.08;filter:blur(70px);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-6rem;left:8rem;width:18rem;height:18rem;border-radius:9999px;background:rgb(245 158 11);opacity:.05;filter:blur(90px);pointer-events:none;"></div>

    {{-- Avatar --}}
    <div style="position:relative;z-index:1;flex-shrink:0;">
        <div style="
            width:5rem;height:5rem;border-radius:9999px;
            border:2px solid rgba(245,158,11,.5);
            overflow:hidden;display:flex;align-items:center;justify-content:center;
            background:rgba(245,158,11,.12);
            box-shadow:0 0 0 4px rgba(245,158,11,.1);
        ">
            <img src="{{ $avatar }}" alt="{{ $user?->name }}"
                 style="width:100%;height:100%;object-fit:cover;border-radius:9999px;" />
        </div>
        {{-- Online dot --}}
        <span style="
            position:absolute;bottom:2px;right:2px;
            width:.85rem;height:.85rem;border-radius:9999px;
            background:#22c55e;border:2px solid rgb(20 15 8);
        "></span>
    </div>

    {{-- Greeting text --}}
    <div style="position:relative;z-index:1;flex:1;min-width:0;">
        <p style="font-size:.8rem;color:rgba(245,158,11,.7);font-weight:600;letter-spacing:.12em;text-transform:uppercase;margin:0 0 .3rem;">
            {{ $greeting }}
        </p>
        <h2 style="font-size:1.6rem;font-weight:800;color:#fff;letter-spacing:-.03em;margin:0 0 .35rem;line-height:1.2;">
            {{ $user?->name }}
        </h2>
        <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
            <span style="
                font-size:.7rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;
                color:rgba(245,158,11,.8);background:rgba(245,158,11,.1);
                border:1px solid rgba(245,158,11,.25);padding:.2rem .65rem;border-radius:9999px;
            ">{{ $roles }}</span>
            <span style="font-size:.78rem;color:rgba(255,255,255,.35);">·</span>
            <span style="font-size:.78rem;color:rgba(255,255,255,.45);">{{ now()->format('l, d F Y') }}</span>
        </div>
    </div>

    {{-- Quick stats --}}
    <div style="position:relative;z-index:1;display:flex;gap:1.25rem;flex-shrink:0;">
        @foreach([
            ['label' => __('admin.dashboard.quick.pending'),  'value' => $newCount,   'color' => '#ef4444', 'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4'],
            ['label' => __('admin.dashboard.quick.active'),   'value' => $activeConv, 'color' => '#f59e0b', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
            ['label' => __('admin.dashboard.quick.today'),    'value' => $todayReqs,  'color' => '#22c55e', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ] as $stat)
            <div style="
                text-align:center;
                background:rgba(255,255,255,.04);
                border:1px solid rgba(255,255,255,.07);
                border-radius:.75rem;
                padding:.85rem 1.25rem;
                min-width:6rem;
            ">
                <svg viewBox="0 0 24 24" fill="none" stroke="{{ $stat['color'] }}" stroke-width="1.5"
                     stroke-linecap="round" stroke-linejoin="round"
                     style="width:1.3rem;height:1.3rem;margin:0 auto .4rem;">
                    <path d="{{ $stat['icon'] }}"/>
                </svg>
                <div style="font-size:1.5rem;font-weight:800;color:#fff;line-height:1;">{{ $stat['value'] }}</div>
                <div style="font-size:.68rem;color:rgba(255,255,255,.4);margin-top:.25rem;white-space:nowrap;">{{ $stat['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>
</x-filament-widgets::widget>
