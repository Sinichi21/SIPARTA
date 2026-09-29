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

    <x-layouts.app.sidebar />

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