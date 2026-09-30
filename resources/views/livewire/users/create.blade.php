<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('users.index') }}" wire:navigate class="text-sm text-blue-700">← Pengguna</a>
        <h1 class="mt-2 text-2xl font-bold">Tambah Pengguna</h1>
        <p class="mt-1 text-sm text-slate-500">
            Admin tidak membuat password. Sistem akan mengirim tautan agar pengguna menentukan password sendiri.
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            @include('livewire.users._form')
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('users.index') }}" wire:navigate class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">Batal</a>
            <button type="submit" class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white">
                Buat & Kirim Aktivasi
            </button>
        </div>
    </form>
</div>
