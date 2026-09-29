{{-- Sidebar --}}
<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-blue-950 text-white
            transition-transform duration-200 lg:translate-x-0"
>
    {{-- Brand --}}
    <div class="flex h-18 items-center gap-3 border-b border-white/10 px-6">
        <div class="flex size-10 items-center justify-center rounded-xl bg-white/10">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="size-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path d="M6 2h9l5 5v15H6z"/>
                <path d="M14 2v6h6"/>
                <path d="M9 13h6"/>
                <path d="M9 17h6"/>
            </svg>
        </div>

        <div>
            <p class="font-bold leading-tight">
                Sistem Persuratan
            </p>

            <p class="text-xs text-blue-200">
                Internal
            </p>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-4 py-5">
        <div class="space-y-1">

            @can('dashboard.view')
                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        'bg-white text-blue-950' => request()->routeIs('dashboard'),
                        'text-blue-100 hover:bg-white/10 hover:text-white' => ! request()->routeIs('dashboard'),
                    ])
                >
                    Dashboard
                </a>
            @endcan

            <p class="px-3 pb-1 pt-5 text-xs font-semibold uppercase tracking-wider text-blue-300">
                Persuratan
            </p>

            @can('letters.view')
                <a
                    href="{{ route('letters.index') }}"
                    wire:navigate
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        'bg-white text-blue-950' => request()->routeIs('letters.index', 'letters.show'),
                        'text-blue-100 hover:bg-white/10 hover:text-white' => ! request()->routeIs('letters.index', 'letters.show'),
                    ])
                >
                    Data SPT
                </a>
            @endcan

            @can('letters.create')
                <a
                    href="{{ route('letters.create') }}"
                    wire:navigate
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        'bg-white text-blue-950' => request()->routeIs('letters.create'),
                        'text-blue-100 hover:bg-white/10 hover:text-white' => ! request()->routeIs('letters.create'),
                    ])
                >
                    Tambah SPT
                </a>
            @endcan

            <p class="px-3 pb-1 pt-5 text-xs font-semibold uppercase tracking-wider text-blue-300">
                Master Data
            </p>

            @can('personnels.view')
                <a
                    href="{{ route('personnels.index') }}"
                    wire:navigate
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        'bg-white text-blue-950' => request()->routeIs('personnels.*'),
                        'text-blue-100 hover:bg-white/10 hover:text-white' => ! request()->routeIs('personnels.*'),
                    ])
                >
                    Personil
                </a>
            @endcan

            @can('units.view')
                <a
                    href="{{ route('units.index') }}"
                    wire:navigate
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        'bg-white text-blue-950' => request()->routeIs('units.*'),
                        'text-blue-100 hover:bg-white/10 hover:text-white' => ! request()->routeIs('units.*'),
                    ])
                >
                    Unit / Tim
                </a>
            @endcan

            @can('activity-types.view')
                <a
                    href="{{ route('activity-types.index') }}"
                    wire:navigate
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        'bg-white text-blue-950' => request()->routeIs('activity-types.*'),
                        'text-blue-100 hover:bg-white/10 hover:text-white' => ! request()->routeIs('activity-types.*'),
                    ])
                >
                    Jenis Kegiatan
                </a>
            @endcan

            @can('letter-types.view')
                <a
                    href="{{ route('letter-types.index') }}"
                    wire:navigate
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        'bg-white text-blue-950' => request()->routeIs('letter-types.*'),
                        'text-blue-100 hover:bg-white/10 hover:text-white' => ! request()->routeIs('letter-types.*'),
                    ])
                >
                    Jenis Surat
                </a>
            @endcan

            <p class="px-3 pb-1 pt-5 text-xs font-semibold uppercase tracking-wider text-blue-300">
                Administrasi
            </p>

            @can('audit-logs.view')
                <a
                    href="{{ route('audit-logs.index') }}"
                    wire:navigate
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        'bg-white text-blue-950' => request()->routeIs('audit-logs.*'),
                        'text-blue-100 hover:bg-white/10 hover:text-white' => ! request()->routeIs('audit-logs.*'),
                    ])
                >
                    Audit Log
                </a>
            @endcan

        </div>
    </nav>

    {{-- Current user --}}
    <div class="border-t border-white/10 p-4">
        <div class="rounded-xl bg-white/5 p-3">
            <p class="truncate text-sm font-semibold">
                {{ auth()->user()?->name }}
            </p>

            <p class="truncate text-xs text-blue-200">
                {{ auth()->user()?->email }}
            </p>

            <form
                method="POST"
                action="{{ route('logout') }}"
                class="mt-3"
            >
                @csrf

                <button
                    type="submit"
                    class="w-full rounded-lg border border-white/15 px-3 py-2 text-xs font-medium
                            text-blue-100 hover:bg-white/10"
                >
                    Keluar
                </button>
            </form>
        </div>
    </div>
</aside>