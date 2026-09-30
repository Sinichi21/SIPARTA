<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <a href="{{ route('administration-profiles.index') }}" wire:navigate class="text-sm text-blue-700">← Kop & Administrasi Surat</a>
        <h1 class="mt-2 text-2xl font-bold">Edit Profil Kop</h1>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($letterheadProfile->logo_path)
        <section class="portal-card">
            <div class="mb-4 flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-bold">Preview Kop Saat Ini</h2>
                    <p class="mt-1 text-xs text-slate-500">Logo dan identitas yang tersimpan saat ini.</p>
                </div>

                <button
                    wire:click="removeLogo"
                    type="button"
                    wire:confirm="Hapus logo kop ini?"
                    class="text-sm font-semibold text-red-600"
                >
                    Hapus Logo
                </button>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50 p-6">
                <x-letterhead-preview :profile="$letterheadProfile" />
            </div>
        </section>
    @endif

    <form wire:submit="save" class="space-y-6">
        @include('livewire.administration-profiles._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('administration-profiles.index') }}" wire:navigate class="spt-action spt-action-back">Kembali</a>
            <button type="submit" class="spt-action spt-action-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
