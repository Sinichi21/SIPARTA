<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <a href="{{ route('roles.index') }}" wire:navigate class="text-sm text-blue-700">← Role & Permission</a>
        <h1 class="mt-2 text-2xl font-bold">Permission: {{ $role->name }}</h1>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-5">
        @foreach($permissionGroups as $group => $items)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold capitalize text-slate-900">{{ $group }}</h2>

                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($items as $permission)
                        <label class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3 text-sm">
                            <input
                                type="checkbox"
                                wire:model="permissions"
                                value="{{ $permission->name }}"
                                class="mt-0.5 rounded border-slate-300"
                            >
                            <span class="font-mono text-xs text-slate-700">{{ $permission->name }}</span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white">
                Simpan Permission
            </button>
        </div>
    </form>
</div>
