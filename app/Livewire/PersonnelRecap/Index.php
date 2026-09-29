<?php

namespace App\Livewire\PersonnelRecap;

use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\Personnel;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $year = '';
    public string $unitId = '';
    public string $status = '';
    public string $activityTypeId = '';
    public string $recordType = 'normal';
    public ?int $selectedPersonnelId = null;
    public bool $showAllHistory = false;
    public bool $showAllPersonnelSpt = false;

    public function mount(): void
    {
        Gate::authorize('reports.view');

        $this->year = (string) now()->year;
    }

    public function updated($property): void
    {
        if (
            in_array(
                $property,
                [
                    'search',
                    'year',
                    'unitId',
                    'status',
                    'activityTypeId',
                    'recordType',
                ],
                true
            )
        ) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'unitId',
            'status',
            'activityTypeId',
        ]);

        $this->year = (string) now()->year;
        $this->recordType = 'normal';

        $this->resetPage();
    }

    public function showDetail(int $personnelId): void
    {
        $this->selectedPersonnelId = $personnelId;
        $this->showAllHistory = false;
    }

    public function closeDetail(): void
    {
        $this->selectedPersonnelId = null;
        $this->showAllHistory = false;
    }

    public function toggleAllHistory(): void
    {
        if (! $this->selectedPersonnelId) {
            return;
        }

        $this->showAllHistory = ! $this->showAllHistory;
    }

    public function openAllPersonnelSpt(): void
    {
        $this->showAllPersonnelSpt = true;
    }

    public function closeAllPersonnelSpt(): void
    {
        $this->showAllPersonnelSpt = false;
    }

    /**
     * Semua query SPT pada Rekap Personil harus melewati method ini.
     *
     * Dengan demikian:
     * - Jumlah SPT
     * - SPT terakhir
     * - Tanggal terakhir
     * - Kegiatan terakhir
     * - Lokasi terakhir
     * - Riwayat di drawer
     *
     * selalu menggunakan filter yang sama.
     */
    private function applySptFilters($query)
    {
        return $query
            ->whereHas(
                'letterType',
                fn (Builder $q) => $q->where('code', 'SPT')
            )
            ->when(
                $this->recordType !== 'all',
                fn ($q) => $q->where(
                    'record_type',
                    $this->recordType
                )
            )
            ->when(
                $this->year !== '',
                fn ($q) => $q->whereYear(
                    'letter_date',
                    (int) $this->year
                )
            )
            ->when(
                $this->activityTypeId !== '',
                fn ($q) => $q->where(
                    'activity_type_id',
                    (int) $this->activityTypeId
                )
            );
    }

    public function exportHistory()
    {
        Gate::authorize('reports.export');

        if (! $this->selectedPersonnelId) {
            return null;
        }

        $personnel = Personnel::query()
            ->with('unit')
            ->findOrFail(
                $this->selectedPersonnelId
            );

        $letters = $this->applySptFilters(
            $personnel->letters()
        )
            ->with('activityType')
            ->orderByDesc('letter_date')
            ->orderByDesc('letters.id')
            ->get();

        $safeName = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '_',
            $personnel->name
        );

        $filename = sprintf(
            'riwayat-spt-%s-%s.csv',
            trim($safeName, '_')
                ?: 'personil',
            now()->format('Ymd-His')
        );

        return response()->streamDownload(
            function () use (
                $personnel,
                $letters
            ) {
                $handle = fopen(
                    'php://output',
                    'w'
                );

                fwrite(
                    $handle,
                    "\xEF\xBB\xBF"
                );

                fputcsv($handle, [
                    'Nama Personil',
                    'NIP',
                    'Unit/Tim',
                    'Nomor SPT',
                    'Tanggal SPT',
                    'Tanggal Mulai',
                    'Tanggal Selesai',
                    'Kegiatan',
                    'Lokasi',
                    'Jenis Record',
                ]);

                foreach ($letters as $letter) {
                    fputcsv($handle, [
                        $personnel->name,
                        $personnel->nip ?? '',
                        $personnel->unit?->name ?? '',
                        $letter->number ?? '',
                        $letter->letter_date
                            ?->format('Y-m-d') ?? '',
                        $letter->start_date
                            ?->format('Y-m-d') ?? '',
                        $letter->end_date
                            ?->format('Y-m-d') ?? '',
                        $letter->subject
                            ?: $letter
                                ->activityType
                                ?->name
                            ?: '',
                        $letter->location ?? '',
                        $letter
                            ->record_type
                            ?->label()
                            ?? 'SPT Normal',
                    ]);
                }

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
            ]
        );
    }

    public function render()
    {
        $query = Personnel::query()
            ->with('unit')
            ->when(
                trim($this->search) !== '',
                function (Builder $q) {
                    $search = trim($this->search);

                    $q->where(
                        fn (Builder $sub) => $sub
                            ->where(
                                'name',
                                'ilike',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'nip',
                                'ilike',
                                "%{$search}%"
                            )
                            ->orWhereHas(
                                'unit',
                                fn (Builder $u) => $u->where(
                                    'name',
                                    'ilike',
                                    "%{$search}%"
                                )
                            )
                    );
                }
            )
            ->when(
                $this->unitId !== '',
                fn (Builder $q) => $q->where(
                    'unit_id',
                    (int) $this->unitId
                )
            )
            ->when(
                $this->status === 'active',
                fn (Builder $q) => $q->where(
                    'is_active',
                    true
                )
            )
            ->when(
                $this->status === 'inactive',
                fn (Builder $q) => $q->where(
                    'is_active',
                    false
                )
            )
            ->when(
                $this->activityTypeId !== '',
                function (Builder $q) {
                    $q->whereHas(
                        'letters',
                        fn ($letter) =>
                            $this->applySptFilters($letter)
                    );
                }
            );

        $personnels = $query
            ->orderBy('name')
            ->paginate(10);

        $personnels
            ->getCollection()
            ->transform(function (Personnel $personnel) {
                /*
                 * Count dan latest berasal dari basis query yang sama.
                 * Jadi tidak bisa lagi terjadi:
                 *
                 * Jumlah SPT = 0
                 * SPT terakhir = ada
                 *
                 * untuk filter yang sama.
                 */
                $sptQuery = $this->applySptFilters(
                    $personnel->letters()
                );

                $personnel->spt_count =
                    (clone $sptQuery)->count();

                $personnel->latest_spt =
                    (clone $sptQuery)
                        ->with('activityType')
                        ->orderByDesc('letter_date')
                        ->orderByDesc('letters.id')
                        ->first();

                return $personnel;
            });

        /*
         * Kartu personil tetap menunjukkan kondisi master personil.
         */
        $totalPersonnel = Personnel::count();

        $activePersonnel = Personnel::query()
            ->where('is_active', true)
            ->count();

        /*
         * Total penugasan mengikuti Jenis Record.
         * Bila tahun/jenis kegiatan dipilih, statistik juga ikut filter.
         */
        $totalAssignments = DB::table('letter_personnel')
            ->join(
                'letters',
                'letter_personnel.letter_id',
                '=',
                'letters.id'
            )
            ->join(
                'letter_types',
                'letters.letter_type_id',
                '=',
                'letter_types.id'
            )
            ->where('letter_types.code', 'SPT')
            ->when(
                $this->recordType !== 'all',
                fn ($q) => $q->where(
                    'letters.record_type',
                    $this->recordType
                )
            )
            ->when(
                $this->year !== '',
                fn ($q) => $q->whereYear(
                    'letters.letter_date',
                    (int) $this->year
                )
            )
            ->when(
                $this->activityTypeId !== '',
                fn ($q) => $q->where(
                    'letters.activity_type_id',
                    (int) $this->activityTypeId
                )
            )
            ->count();

        /*
         * SPT dengan cakupan Seluruh Pegawai tidak ditempelkan ke
         * personil individual. Angkanya ditampilkan terpisah agar
         * data historis tetap terlihat tanpa mengubah jumlah SPT
         * masing-masing personil.
         */
        $allPersonnelSpt = Letter::query()
            ->whereHas(
                'letterType',
                fn (Builder $q) => $q->where('code', 'SPT')
            )
            ->where(
                'personnel_scope',
                Letter::PERSONNEL_SCOPE_ALL
            )
            ->when(
                $this->recordType !== 'all',
                fn (Builder $q) => $q->where(
                    'record_type',
                    $this->recordType
                )
            )
            ->when(
                $this->year !== '',
                fn (Builder $q) => $q->whereYear(
                    'letter_date',
                    (int) $this->year
                )
            )
            ->when(
                $this->activityTypeId !== '',
                fn (Builder $q) => $q->where(
                    'activity_type_id',
                    (int) $this->activityTypeId
                )
            )
            ->count();

        $allPersonnelSptRows = collect();

        if ($this->showAllPersonnelSpt) {
            $allPersonnelSptRows = Letter::query()
                ->whereHas(
                    'letterType',
                    fn (Builder $q) => $q->where('code', 'SPT')
                )
                ->where(
                    'personnel_scope',
                    Letter::PERSONNEL_SCOPE_ALL
                )
                ->when(
                    $this->recordType !== 'all',
                    fn (Builder $q) => $q->where(
                        'record_type',
                        $this->recordType
                    )
                )
                ->when(
                    $this->year !== '',
                    fn (Builder $q) => $q->whereYear(
                        'letter_date',
                        (int) $this->year
                    )
                )
                ->when(
                    $this->activityTypeId !== '',
                    fn (Builder $q) => $q->where(
                        'activity_type_id',
                        (int) $this->activityTypeId
                    )
                )
                ->with('activityType')
                ->orderByDesc('letter_date')
                ->orderByDesc('id')
                ->get();
        }

        /*
         * SPT bulan ini memang selalu berarti bulan berjalan.
         * Filter record type tetap diterapkan.
         */
        $monthAssignments = DB::table('letter_personnel')
            ->join(
                'letters',
                'letter_personnel.letter_id',
                '=',
                'letters.id'
            )
            ->join(
                'letter_types',
                'letters.letter_type_id',
                '=',
                'letter_types.id'
            )
            ->where('letter_types.code', 'SPT')
            ->when(
                $this->recordType !== 'all',
                fn ($q) => $q->where(
                    'letters.record_type',
                    $this->recordType
                )
            )
            ->when(
                $this->activityTypeId !== '',
                fn ($q) => $q->where(
                    'letters.activity_type_id',
                    (int) $this->activityTypeId
                )
            )
            ->whereYear(
                'letters.letter_date',
                now()->year
            )
            ->whereMonth(
                'letters.letter_date',
                now()->month
            )
            ->count();

        /*
         * Statistik unit mengikuti filter utama rekap.
         */
        $unitStats = Unit::query()
            ->leftJoin(
                'personnels',
                'units.id',
                '=',
                'personnels.unit_id'
            )
            ->leftJoin(
                'letter_personnel',
                'personnels.id',
                '=',
                'letter_personnel.personnel_id'
            )
            ->leftJoin(
                'letters',
                'letter_personnel.letter_id',
                '=',
                'letters.id'
            )
            ->leftJoin(
                'letter_types',
                'letters.letter_type_id',
                '=',
                'letter_types.id'
            )
            ->where(function ($q) {
                $q
                    ->where('letter_types.code', 'SPT')
                    ->orWhereNull('letter_types.code');
            })
            ->select('units.name')
            ->selectRaw(
                "
                COUNT(
                    CASE
                        WHEN letter_types.code = 'SPT'
                        AND (? = 'all' OR letters.record_type = ?)
                        AND (? = '' OR EXTRACT(YEAR FROM letters.letter_date) = CAST(? AS INTEGER))
                        AND (? = '' OR letters.activity_type_id = CAST(NULLIF(?, '') AS BIGINT))
                        THEN 1
                    END
                ) as total
                ",
                [
                    $this->recordType,
                    $this->recordType,
                    $this->year,
                    $this->year,
                    $this->activityTypeId,
                    $this->activityTypeId,
                ]
            )
            ->groupBy(
                'units.id',
                'units.name'
            )
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $maxUnit = max(
            1,
            (int) $unitStats->max('total')
        );

        $selectedPersonnel =
            $this->selectedPersonnelId
                ? Personnel::query()
                    ->with('unit')
                    ->find($this->selectedPersonnelId)
                : null;

        $selectedFilteredCount = 0;
        $selectedCurrentYearCount = 0;
        $selectedLatestSpt = null;
        $selectedHistory = collect();

        if ($selectedPersonnel) {
            /*
             * Semua statistik dan riwayat drawer menggunakan filter SPT
             * yang konsisten dengan tabel utama.
             */
            $selectedBaseQuery = $this->applySptFilters(
                $selectedPersonnel->letters()
            );

            $selectedFilteredCount =
                (clone $selectedBaseQuery)->count();

            /*
             * SPT Tahun Ini sengaja dihitung terhadap tahun berjalan,
             * tetapi tetap mengikuti Jenis Record dan Jenis Kegiatan.
             */
            $selectedCurrentYearQuery =
                $selectedPersonnel->letters()
                    ->whereHas(
                        'letterType',
                        fn (Builder $q) =>
                            $q->where('code', 'SPT')
                    )
                    ->when(
                        $this->recordType !== 'all',
                        fn ($q) => $q->where(
                            'record_type',
                            $this->recordType
                        )
                    )
                    ->when(
                        $this->activityTypeId !== '',
                        fn ($q) => $q->where(
                            'activity_type_id',
                            (int) $this->activityTypeId
                        )
                    )
                    ->whereYear(
                        'letter_date',
                        now()->year
                    );

            $selectedCurrentYearCount =
                (clone $selectedCurrentYearQuery)->count();

            $selectedLatestSpt =
                (clone $selectedBaseQuery)
                    ->with('activityType')
                    ->orderByDesc('letter_date')
                    ->orderByDesc('letters.id')
                    ->first();

            $historyQuery =
                (clone $selectedBaseQuery)
                    ->with('activityType')
                    ->orderByDesc('letter_date')
                    ->orderByDesc('letters.id');

            if (! $this->showAllHistory) {
                $historyQuery->limit(5);
            }

            $selectedHistory = $historyQuery->get();
        }

        return view(
            'livewire.personnel-recap.index',
            [
                'personnels' => $personnels,
                'totalPersonnel' => $totalPersonnel,
                'activePersonnel' => $activePersonnel,
                'totalAssignments' => $totalAssignments,
                'allPersonnelSpt' => $allPersonnelSpt,
                'allPersonnelSptRows' => $allPersonnelSptRows,
                'monthAssignments' => $monthAssignments,
                'unitStats' => $unitStats,
                'maxUnit' => $maxUnit,

                'units' => Unit::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(),

                'activityTypes' =>
                    ActivityType::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get(),

                'selectedPersonnel' =>
                    $selectedPersonnel,

                'selectedHistory' =>
                    $selectedHistory,

                'selectedFilteredCount' =>
                    $selectedFilteredCount,

                'selectedCurrentYearCount' =>
                    $selectedCurrentYearCount,

                'selectedLatestSpt' =>
                    $selectedLatestSpt,

                'selectedTotalSpt' =>
                    $selectedFilteredCount,

                'selectedYearSpt' =>
                    $selectedCurrentYearCount,
            ]
            
        );
    }
}
