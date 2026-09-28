<?php

use App\Models\ActivityType;
use App\Models\Personnel;
use App\Services\LetterService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component {
    public string $number = '';
    public string $subject = '';
    public ?int $activity_type_id = null;
    public string $letter_date = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $location = '';
    public string $basis = '';
    public string $description = '';

    public array $personnel_ids = [];

    public string $personnelSearch = '';

    public function mount(): void
    {
        Gate::authorize('letters.create');

        $today = now()->format('Y-m-d');

        $this->letter_date = $today;
        $this->start_date = $today;
        $this->end_date = $today;
    }

    public function save(LetterService $service)
    {
        Gate::authorize('letters.create');

        $data = $this->validate([
            'number' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:500'],
            'activity_type_id' => [
                'required',
                'integer',
                'exists:activity_types,id',
            ],
            'letter_date' => ['required', 'date'],
            'start_date' => ['required', 'date'],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],
            'location' => ['required', 'string', 'max:500'],
            'basis' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'personnel_ids' => ['required', 'array', 'min:1'],
            'personnel_ids.*' => [
                'integer',
                'distinct',
                'exists:personnels,id',
            ],
        ]);

        $letter = $service->createSpt(
            $data,
            Auth::id()
        );

        session()->flash(
            'success',
            'SPT berhasil dibuat sebagai draft.'
        );

        return $this->redirectRoute(
            'letters.show',
            ['letter' => $letter->id],
            navigate: true
        );
    }

    public function with(): array
    {
        return [
            'activityTypes' => ActivityType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'personnels' => Personnel::query()
                ->where('is_active', true)
                ->when(
                    filled($this->personnelSearch),
                    fn ($query) =>
                        $query->where(
                            'name',
                            'ilike',
                            '%' . trim($this->personnelSearch) . '%'
                        )
                )
                ->orderBy('name')
                ->limit(100)
                ->get(),
        ];
    }
};
?>

<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Tambah SPT</h1>
        <p class="mt-1 text-sm text-slate-500">
            Buat Surat Perintah Tugas baru.
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-lg font-semibold text-blue-900">
                Informasi SPT
            </h2>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Nomor SPT</label>
                    <input wire:model="number" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Jenis Kegiatan *</label>
                    <select wire:model="activity_type_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">Pilih kegiatan</option>
                        @foreach ($activityTypes as $activityType)
                            <option value="{{ $activityType->id }}">
                                {{ $activityType->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('activity_type_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Perihal *</label>
                    <input wire:model="subject" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Tanggal Surat *</label>
                    <input type="date" wire:model="letter_date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Lokasi *</label>
                    <input wire:model="location" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('location') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Tanggal Mulai *</label>
                    <input type="date" wire:model="start_date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Tanggal Selesai *</label>
                    <input type="date" wire:model="end_date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('end_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Dasar Surat</label>
                    <textarea wire:model="basis" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Keterangan</label>
                    <textarea wire:model="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-blue-900">
                    Personil
                </h2>

                <span class="text-sm text-slate-500">
                    {{ count($personnel_ids) }} dipilih
                </span>
            </div>

            <input
                type="search"
                wire:model.live.debounce.300ms="personnelSearch"
                placeholder="Cari personil..."
                class="mt-4 w-full rounded-lg border border-slate-300 px-3 py-2"
            >

            <div class="mt-4 max-h-80 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200">
                @foreach ($personnels as $personnel)
                    <label class="flex cursor-pointer items-center gap-3 p-3 hover:bg-blue-50">
                        <input
                            type="checkbox"
                            wire:model="personnel_ids"
                            value="{{ $personnel->id }}"
                            class="rounded border-slate-300 text-blue-700"
                        >

                        <span>
                            <span class="block text-sm font-medium">
                                {{ $personnel->name }}
                            </span>

                            <span class="text-xs text-slate-500">
                                {{ $personnel->nip ?: 'Tanpa NIP' }}
                                @if ($personnel->position)
                                    · {{ $personnel->position }}
                                @endif
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>

            @error('personnel_ids')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </section>

        <div class="flex justify-end gap-3">
            <a
                href="{{ route('letters.index') }}"
                wire:navigate
                class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium"
            >
                Batal
            </a>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="save">
                    Simpan Draft
                </span>
                <span wire:loading wire:target="save">
                    Menyimpan...
                </span>
            </button>
        </div>
    </form>
</div>