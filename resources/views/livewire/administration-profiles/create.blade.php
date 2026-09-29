<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <a href="{{ route('administration-profiles.index') }}" wire:navigate class="text-sm text-blue-700">← Kop & Administrasi Surat</a>
        <h1 class="mt-2 text-2xl font-bold">Profil Kop Baru</h1>
    </div>

    <form wire:submit="save" class="space-y-6">
        @include('livewire.administration-profiles._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('administration-profiles.index') }}" wire:navigate class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">Batal</a>
            <button type="submit" class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white">Simpan Profil</button>
        </div>
    </form>
</div>
