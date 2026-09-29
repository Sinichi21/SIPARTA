<aside
    id="app-sidebar"
    :class="{ 'sidebar-expanded': sidebarOpen }"
    @keydown.escape.window="sidebarOpen = false"
    x-trap.inert.noscroll="sidebarOpen && window.innerWidth < 1024"
    class="app-sidebar fixed inset-y-0 left-0 z-50 flex flex-col text-white"
>
    <div class="sidebar-brand">
        <span class="sidebar-brand-icon"><x-app.icon name="document" class="size-6" /></span>
        <div class="min-w-0 flex-1"><p>Persuratan Komdigi</p><span>Administrasi &amp; Penugasan</span></div>
        <button type="button" @click="sidebarOpen = false" class="sidebar-mobile-close lg:hidden" aria-label="Tutup menu"><x-app.icon name="close" /></button>
    </div>
    <nav aria-label="Navigasi utama" class="sidebar-scroll sidebar-navigation">
        @can('dashboard.view')
            <x-app.nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Dashboard</x-app.nav-link>
        @endcan

        @canany(['letters.view', 'reports.view', 'letters.import'])
            <div class="sidebar-section">
                <p class="sidebar-section-label">Persuratan</p>
                @php($sptActive = request()->routeIs('letters.*', 'spt-recap.*', 'personnel-recap.*', 'spt-import.*'))
                <div x-data="{ open: {{ $sptActive ? 'true' : 'false' }} }" wire:key="spt-menu-{{ $sptActive ? 'active' : 'inactive' }}">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="spt-submenu" @class(['sidebar-link sidebar-group-toggle', 'is-parent-active' => $sptActive])>
                        <x-app.icon name="document" /><span>SPT</span><x-app.icon name="chevron" class="sidebar-chevron" x-bind:class="{ 'rotate-180': open }" />
                    </button>
                    <div id="spt-submenu" x-cloak x-show="open" class="sidebar-submenu">
                        @can('letters.view')<x-app.nav-link :href="route('letters.index')" :active="request()->routeIs('letters.*')" sub>Data SPT</x-app.nav-link>@endcan
                        @can('reports.view')
                            <x-app.nav-link :href="route('spt-recap.index')" :active="request()->routeIs('spt-recap.*')" sub>Rekap SPT</x-app.nav-link>
                            <x-app.nav-link :href="route('personnel-recap.index')" :active="request()->routeIs('personnel-recap.*')" sub>Rekap Personil</x-app.nav-link>
                        @endcan
                        @can('letters.import')<x-app.nav-link :href="route('spt-import.index')" :active="request()->routeIs('spt-import.*')" sub>Import SPT Lama</x-app.nav-link>@endcan
                    </div>
                </div>
            </div>
        @endcanany

        @canany(['personnels.view', 'personnels.merge', 'units.view', 'activity-types.view', 'letter-types.view'])
            <div class="sidebar-section">
                <p class="sidebar-section-label">Master Data</p>
                @can('personnels.view')<x-app.nav-link :href="route('personnels.index')" :active="request()->routeIs('personnels.*')" icon="users">Personil</x-app.nav-link>@endcan
                @can('personnels.merge')<x-app.nav-link :href="route('personnel-duplicates.index')" :active="request()->routeIs('personnel-duplicates.*')" sub>Deteksi Duplikat</x-app.nav-link>@endcan
                @can('units.view')<x-app.nav-link :href="route('units.index')" :active="request()->routeIs('units.*')" icon="building">Unit / Tim Kerja</x-app.nav-link>@endcan
                @can('activity-types.view')<x-app.nav-link :href="route('activity-types.index')" :active="request()->routeIs('activity-types.*')" icon="list">Jenis Kegiatan</x-app.nav-link>@endcan
                @can('letter-types.view')<x-app.nav-link :href="route('letter-types.index')" :active="request()->routeIs('letter-types.*')" icon="document">Jenis Surat</x-app.nav-link>@endcan
            </div>
        @endcanany

        @can('audit-logs.view')
            <div class="sidebar-section"><p class="sidebar-section-label">Pengaturan</p><x-app.nav-link :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.*')" icon="clock">Log Aktivitas</x-app.nav-link></div>
        @endcan
    
        @can('settings.view')
            <div class="sidebar-section">
                <p class="sidebar-section-label">Pengaturan</p>

                <x-app.nav-link
                    :href="route('letter-templates.index')"
                    :active="request()->routeIs('letter-templates.*')"
                    icon="document"
                >
                    Template Surat
                </x-app.nav-link>
            </div>
        @endcan

        @can('settings.view')
            <div class="sidebar-section">
                <p class="sidebar-section-label">Administrasi Surat</p>

                <x-app.nav-link
                    :href="route('administration-profiles.index')"
                    :active="request()->routeIs('administration-profiles.*')"
                    icon="building"
                >
                    Kop & Administrasi
                </x-app.nav-link>
            </div>
        @endcan
</nav>
    <div class="sidebar-footer">
        <a href="{{ route('profile.edit') }}" wire:navigate @click="sidebarOpen = false" class="sidebar-account" aria-label="Pengaturan profil">
            <span class="sidebar-avatar">{{ mb_strtoupper(mb_substr(auth()->user()?->name ?? 'U', 0, 1)) }}</span>
            <span class="min-w-0"><strong>{{ auth()->user()?->name }}</strong><span>{{ auth()->user()?->getRoleNames()->first() ?: 'Pengguna' }}</span></span>
        </a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="sidebar-logout" aria-label="Keluar dari aplikasi" title="Keluar"><x-app.icon name="logout" /></button></form>
    </div>
</aside>
