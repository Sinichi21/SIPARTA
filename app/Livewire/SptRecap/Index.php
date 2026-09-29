<?php

namespace App\Livewire\SptRecap;

use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\Personnel;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $year = '';

    public string $status = '';

    public string $activityTypeId = '';

    public string $personnelId = '';

    public string $unitId = '';

    public string $location = '';

    public string $recordType = 'normal';

    #[Locked]
    public ?int $selectedLetterId = null;

    public function mount(): void
    {
        Gate::authorize('reports.view');
        $this->year = (string) now()->year;
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'year', 'status', 'activityTypeId', 'personnelId', 'unitId', 'location', 'recordType'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'activityTypeId', 'personnelId', 'unitId', 'location']);
        $this->year = (string) now()->year;
        $this->recordType = 'normal';
        $this->resetPage();
    }

    public function showDetail(int $letterId): void
    {
        Gate::authorize('letters.view');
        $this->resetErrorBag();
        $this->baseQuery()->findOrFail($letterId);
        $this->selectedLetterId = $letterId;
    }

    public function closeDetail(): void
    {
        $this->selectedLetterId = null;
    }

    public function downloadAttachment(int $attachmentId)
    {
        Gate::authorize('reports.view');
        Gate::authorize('letters.view');
        $letter = $this->baseQuery()->findOrFail($this->selectedLetterId);
        $attachment = $letter->attachments()->findOrFail($attachmentId);

        if (! Storage::exists($attachment->path)) {
            $this->addError('download', 'Dokumen tidak ditemukan di penyimpanan.');

            return null;
        }

        return Storage::download($attachment->path, $attachment->original_name);
    }

    private function baseQuery(): Builder
    {
        return Letter::query()
            ->whereHas('letterType', fn (Builder $query) => $query->where('code', 'SPT'));
    }

    private function filteredQuery(): Builder
    {
        return $this->baseQuery()
            ->when($this->year !== '', fn (Builder $q) => $q->whereYear('letter_date', (int) $this->year))
            ->when($this->status !== '', fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->activityTypeId !== '', fn (Builder $q) => $q->where('activity_type_id', (int) $this->activityTypeId))
            ->when($this->personnelId !== '', fn (Builder $q) => $q->whereHas('personnels', fn (Builder $p) => $p->whereKey((int) $this->personnelId)))
            ->when($this->unitId !== '', fn (Builder $q) => $q->whereHas('personnels', fn (Builder $p) => $p->where('unit_id', (int) $this->unitId)))
            ->when($this->location !== '', fn (Builder $q) => $q->where('location', 'ilike', '%'.trim($this->location).'%'))
            ->when($this->recordType !== 'all', fn (Builder $q) => $q->where('record_type', $this->recordType))
            ->when(trim($this->search) !== '', function (Builder $q) {
                $search = trim($this->search);

                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('number', 'ilike', "%{$search}%")
                        ->orWhere('subject', 'ilike', "%{$search}%")
                        ->orWhere('location', 'ilike', "%{$search}%")
                        ->orWhereHas('personnels', fn (Builder $p) => $p->where('name', 'ilike', "%{$search}%"));
                });
            });
    }

    public function render()
    {
        $query = $this->filteredQuery();

        $letters = (clone $query)
            ->with(['activityType', 'personnels.unit', 'letterType'])
            ->withCount('personnels')
            ->orderByDesc('letter_date')
            ->orderByDesc('id')
            ->paginate(10);

        $totalSpt = (clone $query)->count();
        $monthSpt = (clone $query)
            ->whereYear('letter_date', now()->year)
            ->whereMonth('letter_date', now()->month)
            ->count();

        $personnelCount = Personnel::query()
            ->whereHas('letters', function (Builder $q) use ($query) {
                $q->whereIn('letters.id', (clone $query)->select('letters.id'));
            })
            ->count();

        $activityCount = (clone $query)->whereNotNull('activity_type_id')->distinct('activity_type_id')->count('activity_type_id');

        $monthlyRaw = $this->baseQuery()
            ->when($this->recordType !== 'all', fn (Builder $q) => $q->where('record_type', $this->recordType))
            ->selectRaw('EXTRACT(MONTH FROM letter_date)::int as month, COUNT(*) as total')
            ->whereYear('letter_date', (int) ($this->year ?: now()->year))
            ->groupByRaw('EXTRACT(MONTH FROM letter_date)')
            ->orderByRaw('EXTRACT(MONTH FROM letter_date)')
            ->pluck('total', 'month');

        $monthly = collect(range(1, 12))->map(fn ($month) => [
            'month' => $month,
            'label' => now()->month($month)->translatedFormat('M'),
            'total' => (int) ($monthlyRaw[$month] ?? 0),
        ]);

        $maxMonthly = max(1, (int) $monthly->max('total'));

        $activities = $this->baseQuery()
            ->when($this->recordType !== 'all', fn (Builder $q) => $q->where('record_type', $this->recordType))
            ->join('activity_types', 'letters.activity_type_id', '=', 'activity_types.id')
            ->select('activity_types.name', DB::raw('COUNT(*) as total'))
            ->whereYear('letters.letter_date', (int) ($this->year ?: now()->year))
            ->groupBy('activity_types.id', 'activity_types.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $selectedLetter = $this->selectedLetterId
            ? Letter::query()->with(['activityType', 'personnels.unit', 'attachments', 'letterType'])->find($this->selectedLetterId)
            : null;

        return view('livewire.spt-recap.index', [
            'letters' => $letters,
            'totalSpt' => $totalSpt,
            'monthSpt' => $monthSpt,
            'personnelCount' => $personnelCount,
            'activityCount' => $activityCount,
            'monthly' => $monthly,
            'maxMonthly' => $maxMonthly,
            'activities' => $activities,
            'activityTypes' => ActivityType::query()->where('is_active', true)->orderBy('name')->get(),
            'personnels' => Personnel::query()->where('is_active', true)->orderBy('name')->get(),
            'units' => Unit::query()->where('is_active', true)->orderBy('name')->get(),
            'years' => $this->baseQuery()->whereNotNull('letter_date')->selectRaw('EXTRACT(YEAR FROM letter_date)::int as year')->distinct()->orderByDesc('year')->pluck('year'),
            'selectedLetter' => $selectedLetter,
        ]);
    }
}
