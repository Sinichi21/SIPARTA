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
    class="min-h-screen bg-slate-50 text-slate-900 antialiased
           dark:bg-slate-950 dark:text-slate-100"
>
    <div
        x-data="{ sidebarOpen: false }"
        class="min-h-screen"
    >
        <x-layouts.app.sidebar />

        <div class="lg:pl-72">
            <x-layouts.app.header />

            <main class="p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>

</html>