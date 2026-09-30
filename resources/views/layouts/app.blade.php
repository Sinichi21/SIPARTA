<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        {{ $title ?? config('app.name', 'Sistem Persuratan') }}
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    @livewireStyles
</head>

<body
    class="admin-app min-h-screen bg-slate-50 text-slate-900 antialiased
           dark:bg-slate-950 dark:text-slate-100"
>
    <div
        x-data="{ sidebarOpen: false, desktop: window.innerWidth >= 1024 }"
        @resize.window.debounce.100ms="desktop = window.innerWidth >= 1024; if (desktop) sidebarOpen = false"
        class="min-h-screen"
    >
        <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-950/40 lg:hidden" aria-hidden="true"></div>
        <x-app.sidebar />

        <div class="app-workspace">
            <x-app.header />

            <main id="main-content" class="app-content p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>

</html>
