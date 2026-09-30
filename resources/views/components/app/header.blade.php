<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="flex h-16 items-center gap-4 px-4 sm:px-6">
        <button type="button" @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen" aria-controls="app-sidebar" class="sidebar-hamburger rounded-lg p-2 text-slate-600 lg:hidden" :aria-label="sidebarOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi'">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        @can('letters.view')
            <form action="{{ route('letters.index') }}" method="GET" class="hidden max-w-xl flex-1 sm:block">
                <div class="relative">
                    <input name="search" type="search" aria-label="Cari SPT" placeholder="Cari nomor SPT, kegiatan, atau lokasi..." class="w-full !bg-slate-50 !pr-12">
                    <button type="submit" aria-label="Cari" class="absolute inset-y-0 right-0 px-3 text-slate-500"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg></button>
                </div>
            </form>
        @endcan
        <a href="{{ route('profile.edit') }}" wire:navigate class="ml-auto flex min-w-0 items-center gap-3 rounded-lg" aria-label="Pengaturan profil">
            <div class="grid size-10 shrink-0 place-items-center rounded-full border border-blue-100 bg-blue-50 font-bold text-blue-700">{{ mb_strtoupper(mb_substr(auth()->user()?->name ?? 'U', 0, 1)) }}</div>
            <div class="hidden min-w-0 sm:block"><p class="max-w-48 truncate text-sm font-semibold text-slate-800">{{ auth()->user()?->name }}</p><p class="text-xs text-slate-500">{{ auth()->user()?->getRoleNames()->first() ?: 'Pengguna' }}</p></div>
            <svg class="size-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </a>
    </div>
</header>
