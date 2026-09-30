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
            <a href="{{ route('letter-templates.index') }}" wire:navigate class="spt-action spt-action-back">Batal</a>
            <button type="submit" class="spt-action spt-action-primary">Simpan Template</button>
        </div>
    </form>
</div>
