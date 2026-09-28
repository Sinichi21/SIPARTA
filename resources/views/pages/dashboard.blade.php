<?php

use App\Enums\LetterStatus;
use App\Models\Letter;
use App\Models\Personnel;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component {
    public function mount(): void
    {
        Gate::authorize('dashboard.view');
    }

    public function with(): array
    {
        $sptQuery = Letter::query()
            ->whereHas(
                'letterType',
                fn ($query) => $query->where('code', 'SPT')
            );

        return [
            'totalSpt' => (clone $sptQuery)->count(),

            'monthlySpt' => (clone $sptQuery)
                ->whereYear('letter_date', now()->year)
                ->whereMonth('letter_date', now()->month)
                ->count(),

            'draftSpt' => (clone $sptQuery)
                ->where('status', LetterStatus::Draft->value)
                ->count(),

            'publishedSpt' => (clone $sptQuery)
                ->where('status', LetterStatus::Published->value)
                ->count(),

            'activePersonnel' => Personnel::query()
                ->where('is_active', true)
                ->count(),

            'latestLetters' => Letter::query()
                ->with([
                    'activityType',
                    'personnels',
                ])
                ->whereHas(
                    'letterType',
                    fn ($query) => $query->where('code', 'SPT')
                )
                ->latest('letter_date')
                ->latest('id')
                ->limit(8)
                ->get(),
        ];
    }
};
?>

<div class="space-y-7">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
            Dashboard
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Ringkasan data persuratan dan penugasan.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Total SPT
            </p>
            <p class="mt-2 text-3xl font-bold text-blue-950">
                {{ number_format($totalSpt) }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                SPT Bulan Ini
            </p>
            <p class="mt-2 text-3xl font-bold text-blue-700">
                {{ number_format($monthlySpt) }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Draft
            </p>
            <p class="mt-2 text-3xl font-bold text-amber-600">
                {{ number_format($draftSpt) }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Diterbitkan
            </p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">
                {{ number_format($publishedSpt) }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Personil Aktif
            </p>
            <p class="mt-2 text-3xl font-bold text-blue-950">
                {{ number_format($activePersonnel) }}
            </p>
        </div>

    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div>
                <h2 class="font-semibold">
                    SPT Terbaru
                </h2>

                <p class="text-sm text-slate-500">
                    Daftar SPT terakhir yang dicatat.
                </p>
            </div>

            @can('letters.view')
                <a
                    href="{{ route('letters.index') }}"
                    wire:navigate
                    class="text-sm font-semibold text-blue-700 hover:text-blue-900"
                >
                    Lihat semua
                </a>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-blue-900">
                            Nomor
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-blue-900">
                            Kegiatan
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-blue-900">
                            Tanggal
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-blue-900">
                            Personil
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-blue-900">
                            Status
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($latestLetters as $letter)
                        <tr>
                            <td class="px-5 py-3 text-sm font-medium">
                                <a
                                    href="{{ route('letters.show', $letter) }}"
                                    wire:navigate
                                    class="text-blue-700 hover:underline"
                                >
                                    {{ $letter->number ?: 'Draft #' . $letter->id }}
                                </a>
                            </td>

                            <td class="px-5 py-3 text-sm">
                                {{ $letter->activityType?->name ?: '-' }}
                            </td>

                            <td class="px-5 py-3 text-sm">
                                {{ $letter->letter_date?->format('d/m/Y') ?: '-' }}
                            </td>

                            <td class="px-5 py-3 text-sm">
                                {{ $letter->personnels->count() }}
                            </td>

                            <td class="px-5 py-3 text-sm">
                                {{ $letter->status->label() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="5"
                                class="px-5 py-10 text-center text-sm text-slate-500"
                            >
                                Belum ada SPT.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>