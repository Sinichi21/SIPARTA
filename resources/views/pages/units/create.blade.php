<?php

use App\Models\Unit;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component {
    public string $code = '';
    public string $name = '';
    public string $description = '';

    public function mount(): void
    {
        Gate::authorize('units.manage');
    }

    public function save(AuditService $audit)
    {
        Gate::authorize('units.manage');

        $data = $this->validate([
            'code' => [
                'nullable',
                'string',
                'max:50',
                'unique:units,code',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $data['code'] = filled($data['code'])
            ? strtoupper(trim($data['code']))
            : null;

        $data['name'] = trim($data['name']);

        $data['description'] = filled($data['description'])
            ? trim($data['description'])
            : null;

        $data['is_active'] = true;

        $unit = Unit::create($data);

        $audit->created($unit);

        session()->flash(
            'success',
            'Unit berhasil ditambahkan.'
        );

        return $this->redirectRoute(
            'units.index',
            navigate: true
        );
    }
};
?>

<div class="mx-auto max-w-3xl space-y-6">

    <div>
        <h1 class="text-2xl font-bold">
            Tambah Unit
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Tambahkan unit atau tim baru.
        </p>
    </div>

    <form
        wire:submit="save"
        class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
    >
        <div>
            <label class="mb-1 block text-sm font-medium">
                Kode
            </label>

            <input
                wire:model="code"
                class="w-full rounded-lg border border-slate-300 px-3 py-2"
            >

            @error('code')
                <p class="mt-1 text-xs text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">
                Nama Unit *
            </label>

            <input
                wire:model="name"
                class="w-full rounded-lg border border-slate-300 px-3 py-2"
            >

            @error('name')
                <p class="mt-1 text-xs text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">
                Deskripsi
            </label>

            <textarea
                wire:model="description"
                rows="4"
                class="w-full rounded-lg border border-slate-300 px-3 py-2"
            ></textarea>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">

            <a
                href="{{ route('units.index') }}"
                wire:navigate
                class="rounded-lg border border-slate-300 px-4 py-2"
            >
                Batal
            </a>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="rounded-lg bg-blue-700 px-5 py-2 font-semibold text-white"
            >
                Simpan
            </button>
        </div>
    </form>
</div>