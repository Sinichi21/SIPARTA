<?php

namespace App\Livewire\PersonnelRecap;

use App\Models\ActivityType;
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

    public function mount(): void
    {
        Gate::authorize('reports.view');
        $this->year = (string) now()->year;
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'year', 'unitId', 'status', 'activityTypeId', 'recordType'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'unitId', 'status', 'activityTypeId']);
        $this->year = (string) now()->year;
        $this->recordType = 'normal';
        $this->resetPage();
    }

    public function showDetail(int $personnelId): void
    {
        $this->selectedPersonnelId = $personnelId;
    }

    public function closeDetail(): void
    {
        $this->selectedPersonnelId = null;
    }

    public function render()
    {
        $query = Personnel::query()
            ->with('unit')
            ->when(trim($this->search) !== '', function (Builder $q) {
                $search = trim($this->search);
                $q->where(fn (Builder $sub) => $sub
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('nip', 'ilike', "%{$search}%")
                    ->orWhereHas('unit', fn (Builder $u) => $u->where('name', 'ilike', "%{$search}%"))
                );
            })
            ->when($this->unitId !== '', fn (Builder $q) => $q->where('unit_id', (int) $this->unitId))
            ->when($this->status === 'active', fn (Builder $q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn (Builder $q) => $q->where('is_active', false))
            ->when($this->activityTypeId !== '', fn (Builder $q) => $q->whereHas('letters', function (Builder $letter) {
                $letter->where('activity_type_id', (int) $this->activityTypeId)
                    ->whereHas('letterType', fn (Builder $type) => $type->where('code', 'SPT'))
                    ->when($this->recordType !== 'all', fn (Builder $q) => $q->where('record_type', $this->recordType));
            }));

        $personnels = $query->orderBy('name')->paginate(10);

        $personnels->getCollection()->transform(function (Personnel $personnel) {
            $sptQuery = $personnel->letters()
                ->whereHas('letterType', fn (Builder $q) => $q->where('code', 'SPT'))
                ->when($this->recordType !== 'all', fn (Builder $q) => $q->where('record_type', $this->recordType));

            $personnel->spt_count = (clone $sptQuery)
                ->when($this->year !== '', fn ($q) => $q->whereYear('letter_date', (int) $this->year))
                ->count();

            $personnel->latest_spt = (clone $sptQuery)
                ->with('activityType')
                ->orderByDesc('letter_date')
                ->orderByDesc('letters.id')
                ->first();

            return $personnel;
        });

        $totalPersonnel = Personnel::count();
        $activePersonnel = Personnel::where('is_active', true)->count();
        $totalAssignments = DB::table('letter_personnel')
            ->join('letters', 'letter_personnel.letter_id', '=', 'letters.id')
            ->join('letter_types', 'letters.letter_type_id', '=', 'letter_types.id')
            ->where('letter_types.code', 'SPT')
            ->when($this->recordType !== 'all', fn ($q) => $q->where('letters.record_type', $this->recordType))
            ->count();
        $monthAssignments = DB::table('letter_personnel')
            ->join('letters', 'letter_personnel.letter_id', '=', 'letters.id')
            ->join('letter_types', 'letters.letter_type_id', '=', 'letter_types.id')
            ->where('letter_types.code', 'SPT')
            ->when($this->recordType !== 'all', fn ($q) => $q->where('letters.record_type', $this->recordType))
            ->whereYear('letters.letter_date', now()->year)
            ->whereMonth('letters.letter_date', now()->month)
            ->count();

        $unitStats = Unit::query()
            ->leftJoin('personnels', 'units.id', '=', 'personnels.unit_id')
            ->leftJoin('letter_personnel', 'personnels.id', '=', 'letter_personnel.personnel_id')
            ->leftJoin('letters', 'letter_personnel.letter_id', '=', 'letters.id')
            ->leftJoin('letter_types', 'letters.letter_type_id', '=', 'letter_types.id')
            ->where(function ($q) {
                $q->where('letter_types.code', 'SPT')->orWhereNull('letter_types.code');
            })
            ->select('units.name')
            ->selectRaw(
                "COUNT(CASE WHEN letter_types.code = 'SPT' AND (? = 'all' OR letters.record_type = ?) THEN 1 END) as total",
                [$this->recordType, $this->recordType]
            )
            ->groupBy('units.id', 'units.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $maxUnit = max(1, (int) $unitStats->max('total'));

        $selectedPersonnel = $this->selectedPersonnelId
            ? Personnel::query()->with('unit')->find($this->selectedPersonnelId)
            : null;

        $selectedHistory = $selectedPersonnel
            ? $selectedPersonnel->letters()
                ->with('activityType')
                ->whereHas('letterType', fn (Builder $q) => $q->where('code', 'SPT'))
                ->when($this->recordType !== 'all', fn (Builder $q) => $q->where('record_type', $this->recordType))
                ->orderByDesc('letter_date')
                ->orderByDesc('letters.id')
                ->limit(5)
                ->get()
            : collect();

        return view('livewire.personnel-recap.index', [
            'personnels' => $personnels,
            'totalPersonnel' => $totalPersonnel,
            'activePersonnel' => $activePersonnel,
            'totalAssignments' => $totalAssignments,
            'monthAssignments' => $monthAssignments,
            'unitStats' => $unitStats,
            'maxUnit' => $maxUnit,
            'units' => Unit::query()->where('is_active', true)->orderBy('name')->get(),
            'activityTypes' => ActivityType::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedPersonnel' => $selectedPersonnel,
            'selectedHistory' => $selectedHistory,
        ]);
    }
}
