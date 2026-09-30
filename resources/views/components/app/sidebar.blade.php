<aside
    id="app-sidebar"
    :class="{ 'sidebar-expanded': sidebarOpen }"
    @keydown.escape.window="sidebarOpen = false"
    x-trap.inert.noscroll="sidebarOpen && !desktop"
    :inert="!desktop && !sidebarOpen"
    :aria-hidden="!desktop && !sidebarOpen"
    class="app-sidebar fixed inset-y-0 left-0 z-50 flex flex-col text-white"
>
    <div class="sidebar-brand">
        <span class="sidebar-brand-icon"><x-app.icon name="document" class="size-6" /></span>
        <div class="min-w-0 flex-1"><p>Persuratan Komdigi</p><span>Administrasi &amp; Penugasan</span></div>
        <button type="button" @click="sidebarOpen = false" class="sidebar-mobile-close lg:hidden" aria-label="Tutup menu"><x-app.icon name="close" /></button>
    </div>
    @php
        $groups = collect([
            ['id' => 'spt', 'label' => 'Surat Perintah Tugas', 'icon' => 'document', 'section' => 'work', 'items' => [
                ['label' => 'Data SPT', 'route' => 'letters.index', 'match' => 'letters.*', 'permission' => 'letters.view'],
                ['label' => 'Import SPT Lama', 'route' => 'spt-import.index', 'match' => 'spt-import.*', 'permission' => 'letters.import'],
            ]],
            ['id' => 'reports', 'label' => 'Rekap & Laporan', 'icon' => 'chart', 'section' => 'work', 'items' => [
                ['label' => 'Rekap SPT', 'route' => 'spt-recap.index', 'match' => 'spt-recap.*', 'permission' => 'reports.view'],
                ['label' => 'Rekap Personil', 'route' => 'personnel-recap.index', 'match' => 'personnel-recap.*', 'permission' => 'reports.view'],
            ]],
            ['id' => 'personnel', 'label' => 'Personil & Unit', 'icon' => 'users', 'section' => 'manage', 'items' => [
                ['label' => 'Data Personil', 'route' => 'personnels.index', 'match' => 'personnels.*', 'permission' => 'personnels.view'],
                ['label' => 'Unit / Tim Kerja', 'route' => 'units.index', 'match' => 'units.*', 'permission' => 'units.view'],
                ['label' => 'Deteksi Duplikat', 'route' => 'personnel-duplicates.index', 'match' => 'personnel-duplicates.*', 'permission' => 'personnels.merge'],
            ]],
            ['id' => 'administration', 'label' => 'Administrasi Surat', 'icon' => 'building', 'section' => 'manage', 'items' => [
                ['label' => 'Jenis Surat', 'route' => 'letter-types.index', 'match' => 'letter-types.*', 'permission' => 'letter-types.view'],
                ['label' => 'Jenis Kegiatan', 'route' => 'activity-types.index', 'match' => 'activity-types.*', 'permission' => 'activity-types.view'],
                ['label' => 'Template Surat', 'route' => 'letter-templates.index', 'match' => 'letter-templates.*', 'permission' => 'settings.view'],
                ['label' => 'Kop & Administrasi', 'route' => 'administration-profiles.index', 'match' => 'administration-profiles.*', 'permission' => 'settings.view'],
            ]],
            ['id' => 'access', 'label' => 'Akses & Keamanan', 'icon' => 'shield', 'section' => 'manage', 'items' => [
                ['label' => 'Pengguna', 'route' => 'users.index', 'match' => 'users.*', 'permission' => 'users.view'],
                ['label' => 'Role & Permission', 'route' => 'roles.index', 'match' => 'roles.*', 'permission' => 'roles.view'],
                ['label' => 'Pemulihan Akun', 'route' => 'security.account-recovery', 'match' => 'security.account-recovery', 'permission' => 'users.security.manage'],
                ['label' => 'Log Aktivitas', 'route' => 'audit-logs.index', 'match' => 'audit-logs.*', 'permission' => 'audit-logs.view'],
            ]],
        ])->map(function ($group) {
            $group['items'] = collect($group['items'])->filter(fn ($item) => auth()->user()->can($item['permission']));
            $group['active'] = $group['items']->contains(fn ($item) => request()->routeIs($item['match']));
            return $group;
        })->filter(fn ($group) => $group['items']->isNotEmpty());
        $activeGroup = $groups->firstWhere('active', true)['id'] ?? null;
    @endphp
    <nav
        aria-label="Navigasi utama"
        class="sidebar-scroll sidebar-navigation"
        x-data="{ openGroup: @js($activeGroup) }"
        wire:key="sidebar-navigation-{{ request()->route()?->getName() ?? 'default' }}"
    >
        @can('dashboard.view')
            <x-app.nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Dashboard</x-app.nav-link>
        @endcan
        @foreach(['work' => 'Ruang Kerja', 'manage' => 'Pengelolaan'] as $section => $heading)
            @if($groups->contains('section', $section))
                <div class="sidebar-section">
                    <p class="sidebar-section-label">{{ $heading }}</p>
                    @foreach($groups->where('section', $section) as $group)
                        <x-app.sidebar-group :id="$group['id']" :label="$group['label']" :icon="$group['icon']" :active="$group['active']">
                            @foreach($group['items'] as $item)
                                <x-app.nav-link :href="route($item['route'])" :active="request()->routeIs($item['match'])" sub>{{ $item['label'] }}</x-app.nav-link>
                            @endforeach
                        </x-app.sidebar-group>
                    @endforeach
                </div>
            @endif
        @endforeach
    </nav>
    <div class="sidebar-footer">
        <a href="{{ route('profile.edit') }}" wire:navigate @click="sidebarOpen = false" class="sidebar-account" aria-label="Pengaturan profil">
            <span class="sidebar-avatar">{{ mb_strtoupper(mb_substr(auth()->user()?->name ?? 'U', 0, 1)) }}</span>
            <span class="min-w-0"><strong>{{ auth()->user()?->name }}</strong><span>{{ auth()->user()?->getRoleNames()->first() ?: 'Pengguna' }}</span></span>
        </a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="sidebar-logout" aria-label="Keluar dari aplikasi" title="Keluar"><x-app.icon name="logout" /></button></form>
    </div>
</aside>
