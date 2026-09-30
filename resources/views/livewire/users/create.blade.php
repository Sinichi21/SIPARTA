<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('users.index') }}" wire:navigate class="text-sm text-blue-700">← Pengguna</a>
        <h1 class="mt-2 text-2xl font-bold">Tambah Pengguna</h1>
        <p class="mt-1 text-sm text-slate-500">
            Admin tidak membuat password. Sistem akan mengirim tautan agar pengguna menentukan password sendiri.
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="portal-card">
            @include('livewire.users._form')
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('users.index') }}" wire:navigate class="spt-action spt-action-back">Batal</a>
            <button type="submit" class="spt-action spt-action-primary">
                Buat & Kirim Aktivasi
            </button>
        </div>
    </form>
</div>
