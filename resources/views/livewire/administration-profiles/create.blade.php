<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <a href="{{ route('administration-profiles.index') }}" wire:navigate class="text-sm text-blue-700">← Kop & Administrasi Surat</a>
        <h1 class="mt-2 text-2xl font-bold">Profil Kop Baru</h1>
    </div>

    <form wire:submit="save" class="space-y-6">
        @include('livewire.administration-profiles._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('administration-profiles.index') }}" wire:navigate class="spt-action spt-action-back">Batal</a>
            <button type="submit" class="spt-action spt-action-primary">Simpan Profil</button>
        </div>
    </form>
</div>
