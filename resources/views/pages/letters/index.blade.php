<?php

use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\Personnel;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $year = '';
    public string $month = '';
    public string $activityType = '';
    public string $personnel = '';

    public function mount(): void
    {
        Gate::authorize('letters.view');
    }

    public function updated($property): void
    {
        if (
            in_array($property, [
                'search',
                'status',
                'year',
                'month',
                'activityType',
                'personnel',
            ])
        ) {
            $this->resetPage();
        }
    }

    public function with(): array
    {
        $letters = Letter::query()
            ->with([
                'activityType',
                'personnels',
                'creator',
            ])
            ->whereHas(
                'letterType',
                fn ($query) => $query->where('code', 'SPT')
            )
            ->when(
                filled($this->search),
                function ($query) {
                    $search = trim($this->search);

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('number', 'ilike', "%{$search}%")
                            ->orWhere('subject', 'ilike', "%{$search}%")
                            ->orWhere('location', 'ilike', "%{$search}%");
                    });
                }
            )
            ->when(
                filled($this->status),
                fn ($query) => $query->where(
                    'status',
                    $this->status
                )
            )
            ->when(
                filled($this->year),
                fn ($query) => $query->whereYear(
                    'letter_date',
                    $this->year
                )
            )
            ->when(
                filled($this->month),
                fn ($query) => $query->whereMonth(
                    'letter_date',
                    $this->month
                )
            )
            ->when(
                filled($this->activityType),
                fn ($query) => $query->where(
                    'activity_type_id',
                    $this->activityType
                )
            )
            ->when(
                filled($this->personnel),
                fn ($query) => $query->whereHas(
                    'personnels',
                    fn ($personnelQuery) =>
                        $personnelQuery->where(
                            'personnels.id',
                            $this->personnel
                        )
                )
            )
            ->orderByDesc('letter_date')
            ->orderByDesc('id')
            ->paginate(15);

        return [
            'letters' => $letters,

            'activityTypes' => ActivityType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'personnels' => Personnel::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Data SPT</h1>
            <p class="mt-1 text-sm text-slate-500">
                Data Surat Perintah Tugas dan personil yang ditugaskan.
            </p>
        </div>

        @can('letters.create')
            <a
                href="{{ route('letters.create') }}"
                wire:navigate
                class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800"
            >
                + Tambah SPT
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 lg:grid-cols-6">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari SPT..."
                class="rounded-lg border border-slate-300 px-3 py-2 lg:col-span-2"
            >

            <select wire:model.live="status" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Semua status</option>
                <option value="draft">Draft</option>
                <option value="published">Diterbitkan</option>
                <option value="cancelled">Dibatalkan</option>
            </select>

            <select wire:model.live="activityType" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Semua kegiatan</option>
                @foreach ($activityTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>

            <input
                type="number"
                wire:model.live="year"
                placeholder="Tahun"
                min="2000"
                max="2100"
                class="rounded-lg border border-slate-300 px-3 py-2"
            >

            <select wire:model.live="month" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Semua bulan</option>
                @foreach (range(1, 12) as $number)
                    <option value="{{ $number }}">
                        {{ \Carbon\Carbon::create()->month($number)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">Nomor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">Kegiatan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">Lokasi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">Personil</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-blue-900">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-blue-900">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($letters as $letter)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium">
                                {{ $letter->number ?: 'Belum bernomor' }}
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ $letter->letter_date?->format('d/m/Y') ?: '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ $letter->activityType?->name ?: '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ $letter->location ?: '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ $letter->personnels->count() }} personil
                            </td>

                            <td class="px-4 py-3 text-sm">
                                {{ $letter->status->label() }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <a
                                    href="{{ route('letters.show', $letter) }}"
                                    wire:navigate
                                    class="text-sm font-medium text-blue-700"
                                >
                                    Lihat
                                </a>

                                @if ($letter->status->value === 'draft')
                                    @can('letters.update')
                                        <a
                                            href="{{ route('letters.edit', $letter) }}"
                                            wire:navigate
                                            class="ml-3 text-sm font-medium text-slate-700"
                                        >
                                            Edit
                                        </a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">
                                Belum ada data SPT.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-4">
            {{ $letters->links() }}
        </div>
    </div>
</div>