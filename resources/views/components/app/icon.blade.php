@props(['name' => 'document'])
<svg {{ $attributes->class(['size-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('chart')
            <path d="M4 3v18h17M8 16v-5m5 5V7m5 9V4"/>
            @break
        @case('home')
            <path d="m3 10 9-7 9 7M5 9v12h14V9M9 21v-7h6v7"/>
            @break
        @case('chevron')
            <path d="m6 9 6 6 6-6"/>
            @break
        @case('logout')
            <path d="M9 4H4v16h5M9 12h12m-4-4 4 4-4 4"/>
            @break
        @case('close')
            <path d="m6 6 12 12M18 6 6 18"/>
            @break
        @case('download')
            <path d="M12 3v12m-4-4 4 4 4-4M4 16v5h16v-5"/>
            @break
        @case('edit')
            <path d="m15 4 5 5M4 20l5-1L21 7a2 2 0 0 0-5-5L4 14z"/>
            @break
        @case('pin')
            <path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>
            @break
        @case('shield')
            <path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6zM8 12l3 3 5-6"/>
            @break
        @case('arrow')
            <path d="M4 12h16m-5-5 5 5-5 5"/>
            @break
        @case('archive')
            <rect x="3" y="3" width="18" height="5" rx="1"/><path d="M5 8v13h14V8M9 12h6"/>
            @break
        @case('sun')
            <circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/>
            @break
        @case('users')
            <circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6M21 21v-3a6 6 0 0 0-3-5"/>
            @break
        @case('building')
            <path d="M4 21V3h16v18M2 21h20M9 21v-4h6v4M8 7h1m6 0h1M8 12h1m6 0h1"/>
            @break
        @case('list')
            <path d="M9 6h12M9 12h12M9 18h12M3 6h1M3 12h1M3 18h1"/>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 11h18M7 15h2m3 0h2m3 0h1M7 18h2"/>
            @break
        @default
            <path d="M6 3h8l5 5v13H6zM14 3v6h5M9 13h7M9 17h5"/>
    @endswitch
</svg>
