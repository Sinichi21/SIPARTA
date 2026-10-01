<div class="mx-auto max-w-6xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Tim Personil SPT</h1>
        <p class="mt-1 text-sm text-slate-500">Kelompokkan personil agar pembuatan SPT dapat memilih satu tim sekaligus.</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_1.4fr]">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-blue-900">{{ $editingId ? 'Edit Tim' : 'Tambah Tim' }}</h2>
            <form wire:submit="save" class="mt-4 space-y-4">
                <label class="block"><span class="mb-1 block text-sm font-medium">Nama Tim *</span><input wire:model="name" class="w-full rounded-lg border border-slate-300 px-3 py-2">@error('name')<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>
                <label class="block"><span class="mb-1 block text-sm font-medium">Kode</span><input wire:model="code" class="w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                <label class="block"><span class="mb-1 block text-sm font-medium">Deskripsi</span><textarea wire:model="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea></label>
                <label class="block"><span class="mb-1 block text-sm font-medium">Anggota</span>
                    <select wire:model="personnel_ids" multiple size="12" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        @foreach($personnels as $person)
                            <option value="{{ $person->id }}">{{ $person->name }} — {{ $person->nip ?: 'Tanpa NIP' }}</option>
                        @endforeach
                    </select>
                    <span class="mt-1 block text-xs text-slate-500">Gunakan Ctrl/Command untuk memilih beberapa personil.</span>
                </label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active"> Tim aktif</label>
                <div class="flex gap-2">
                    <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">{{ $editingId ? 'Simpan Perubahan' : 'Tambah Tim' }}</button>
                    @if($editingId)<button type="button" wire:click="resetForm" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Batal</button>@endif
                </div>
            </form>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-blue-900">Daftar Tim</h2>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse($teams as $team)
                    <div class="flex items-center justify-between gap-4 py-4">
                        <div>
                            <div class="font-semibold">{{ $team->name }} @if($team->code)<span class="text-xs text-slate-500">({{ $team->code }})</span>@endif</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $team->personnels_count }} anggota · {{ $team->is_active ? 'Aktif' : 'Nonaktif' }}</div>
                        </div>
                        @can('personnels.update')<button wire:click="edit({{ $team->id }})" class="text-sm font-semibold text-blue-700 hover:underline">Edit</button>@endcan
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">Belum ada tim personil.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
