<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Sistem Persuratan') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">

<div
    x-data="{ sidebarOpen: false }"
    class="min-h-screen"
>
    {{-- Mobile overlay --}}
    <div
        x-show="sidebarOpen"
        x-cloak
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
    ></div>

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

    {{-- Content --}}
    <div class="lg:pl-72">
        {{-- Header --}}
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
            <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">

                <button
                    type="button"
                    @click="sidebarOpen = true"
                    class="rounded-lg border border-slate-200 p-2 lg:hidden"
                >
                    <span class="sr-only">Buka menu</span>

                    <svg
                        class="size-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <div class="hidden lg:block">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Sistem Informasi Persuratan
                    </p>
                </div>

                <div class="text-right">
                    <p class="text-sm font-medium">
                        {{ auth()->user()?->name }}
                    </p>
                </div>
            </div>
        </header>

        <main class="p-4 sm:p-6 lg:p-8">
            {{ $slot }}
        </main>
    </div>
</div>

@livewireScripts
</body>
</html>