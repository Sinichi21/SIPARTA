<header
    class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur
           dark:border-slate-800 dark:bg-slate-900/95"
>
    <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">

        <button
            type="button"
            @click="sidebarOpen = true"
            class="rounded-lg border border-slate-200 p-2 lg:hidden"
        >
            <span class="sr-only">
                Buka menu
            </span>

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