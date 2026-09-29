<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="app-sidebar fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-gradient-to-b from-[#123b6d] to-[#082d56] text-white transition-transform duration-200 lg:translate-x-0"
>
    <div class="flex h-16 items-center gap-3 border-b border-white/10 px-4">
        <div class="flex size-11 items-center justify-center rounded-2xl bg-blue-500/25 ring-1 ring-white/10">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>
        </div>
        <div><p class="font-bold leading-tight">Persuratan Komdigi</p><p class="mt-1 text-xs text-blue-200">Administrasi SPT</p></div>
    </div>

    <nav aria-label="Navigasi utama" class="sidebar-scroll flex-1 overflow-y-auto px-3 py-5 text-sm">
        @can('dashboard.view')
            <a href="{{ route('dashboard') }}" wire:navigate @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 font-semibold transition','bg-blue-500 text-white shadow-lg shadow-blue-950/20'=>request()->routeIs('dashboard'),'text-blue-100 hover:bg-white/10'=>!request()->routeIs('dashboard')])>
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11 12 3l9 8"/><path d="M5 10v10h14V10"/></svg>Dashboard
            </a>
        @endcan

        <p class="px-4 pb-2 pt-6 text-[11px] font-semibold uppercase tracking-[.14em] text-blue-300">Persuratan</p>

        @can('letters.view')
            <div x-data="{ open: {{ request()->routeIs('letters.*','spt-recap.*','personnel-recap.*','spt-import.*') ? 'true' : 'false' }} }">
                <button type="button" :aria-expanded="open" @click="open = !open" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 font-semibold text-blue-100 hover:bg-white/10">
                    <span class="flex items-center gap-3"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/></svg>SPT</span><svg class="size-4 transition" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div x-cloak x-show="open" class="mt-1 space-y-1 pl-6">
                    <a href="{{ route('letters.index') }}" wire:navigate @class(['block rounded-lg px-4 py-2.5','bg-blue-500 text-white'=>request()->routeIs('letters.index','letters.show','letters.edit'),'text-blue-200 hover:bg-white/10'=>!request()->routeIs('letters.index','letters.show','letters.edit')])>Data SPT</a>
                    @can('reports.view')
                        <a href="{{ route('spt-recap.index') }}" wire:navigate @class(['block rounded-lg px-4 py-2.5','bg-blue-500 text-white'=>request()->routeIs('spt-recap.*'),'text-blue-200 hover:bg-white/10'=>!request()->routeIs('spt-recap.*')])>Rekap SPT</a>
                        <a href="{{ route('personnel-recap.index') }}" wire:navigate @class(['block rounded-lg px-4 py-2.5','bg-blue-500 text-white'=>request()->routeIs('personnel-recap.*'),'text-blue-200 hover:bg-white/10'=>!request()->routeIs('personnel-recap.*')])>Rekap Personil</a>
                    @endcan
                    @can('letters.import')<a href="{{ route('spt-import.index') }}" wire:navigate @class(['block rounded-lg px-4 py-2.5','bg-blue-500 text-white'=>request()->routeIs('spt-import.*'),'text-blue-200 hover:bg-white/10'=>!request()->routeIs('spt-import.*')])>Import SPT Lama</a>@endcan
                </div>
            </div>
        @endcan

        <p class="px-4 pb-2 pt-6 text-[11px] font-semibold uppercase tracking-[.14em] text-blue-300">Master Data</p>
        @can('personnels.view')<a href="{{ route('personnels.index') }}" wire:navigate @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium','bg-blue-500 text-white'=>request()->routeIs('personnels.*'),'text-blue-100 hover:bg-white/10'=>!request()->routeIs('personnels.*')])><x-app.icon name="users" />Personil</a>@endcan
        @can('personnels.merge')<a href="{{ route('personnel-duplicates.index') }}" wire:navigate @class(['ml-6 block rounded-lg px-4 py-2 text-xs font-semibold','bg-amber-400/20 text-amber-100'=>request()->routeIs('personnel-duplicates.*'),'text-blue-200 hover:bg-white/10'=>!request()->routeIs('personnel-duplicates.*')])>Deteksi Duplikat</a>@endcan
        @can('units.view')<a href="{{ route('units.index') }}" wire:navigate @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium','bg-blue-500 text-white'=>request()->routeIs('units.*'),'text-blue-100 hover:bg-white/10'=>!request()->routeIs('units.*')])><x-app.icon name="building" />Unit / Tim Kerja</a>@endcan
        @can('activity-types.view')<a href="{{ route('activity-types.index') }}" wire:navigate @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium','bg-blue-500 text-white'=>request()->routeIs('activity-types.*'),'text-blue-100 hover:bg-white/10'=>!request()->routeIs('activity-types.*')])><x-app.icon name="list" />Jenis Kegiatan</a>@endcan
        @can('letter-types.view')<a href="{{ route('letter-types.index') }}" wire:navigate @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium','bg-blue-500 text-white'=>request()->routeIs('letter-types.*'),'text-blue-100 hover:bg-white/10'=>!request()->routeIs('letter-types.*')])><x-app.icon name="document" />Jenis Surat</a>@endcan

        <p class="px-4 pb-2 pt-6 text-[11px] font-semibold uppercase tracking-[.14em] text-blue-300">Pengaturan</p>
        @can('audit-logs.view')<a href="{{ route('audit-logs.index') }}" wire:navigate @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium','bg-blue-500 text-white'=>request()->routeIs('audit-logs.*'),'text-blue-100 hover:bg-white/10'=>!request()->routeIs('audit-logs.*')])><x-app.icon name="clock" />Log Aktivitas</a>@endcan
    </nav>

    <div class="border-t border-white/10 p-4"><div class="rounded-2xl bg-white/5 p-4"><p class="truncate text-sm font-semibold">{{ auth()->user()?->name }}</p><p class="truncate text-xs text-blue-200">{{ auth()->user()?->email }}</p><form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<button class="w-full rounded-xl border border-white/15 px-3 py-2 text-xs font-semibold text-blue-100 hover:bg-white/10">Keluar</button></form></div></div>
</aside>
