<div class="mx-auto max-w-6xl space-y-6">
    <div>
        <a href="{{ route('letter-templates.index') }}" wire:navigate class="text-sm text-blue-700">← Template Surat</a>
        <h1 class="mt-2 text-2xl font-bold">Template Surat Baru</h1>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            Periksa kembali data template.
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        @include('livewire.letter-templates._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('letter-templates.index') }}" wire:navigate class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">Batal</a>
            <button type="submit" class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white">Simpan Template</button>
        </div>
    </form>
</div>
