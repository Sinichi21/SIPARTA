<?php

namespace App\Livewire\SptRecap;

use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\Personnel;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
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
        return Letter::query()->spt();
    }

    private function filteredQuery(bool $applyYear = true): Builder
    {
        return $this->baseQuery()
            ->when($applyYear && $this->year !== '', fn (Builder $q) => $q->whereYear('letter_date', (int) $this->year))
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
        $monthSpt = $this->filteredQuery(false)
            ->whereYear('letter_date', now()->year)
            ->whereMonth('letter_date', now()->month)
            ->count();

        $personnelCount = Personnel::query()
            ->whereHas('letters', function (Builder $q) use ($query) {
                $q->whereIn('letters.id', (clone $query)->select('letters.id'));
            })
            ->count();

        $activityCount = (clone $query)->whereNotNull('activity_type_id')->distinct('activity_type_id')->count('activity_type_id');

        $monthlyRaw = (clone $query)
            ->whereNotNull('letter_date')
            ->get(['letter_date'])
            ->countBy(
                fn (Letter $letter) => (int) $letter->letter_date->month
            );

        $monthly = collect(range(1, 12))->map(fn ($month) => [
            'month' => $month,
            'label' => now()->month($month)->translatedFormat('M'),
            'total' => (int) ($monthlyRaw[$month] ?? 0),
        ]);

        $maxMonthly = max(1, (int) $monthly->max('total'));

        $activities = (clone $query)
            ->whereNotNull('activity_type_id')
            ->with('activityType:id,name')
            ->get(['id', 'activity_type_id'])
            ->groupBy(
                fn (Letter $letter) =>
                    $letter->activityType?->name
                    ?? 'Belum dikategorikan'
            )
            ->map(
                fn ($items, $name) => (object) [
                    'name' => $name,
                    'total' => $items->count(),
                ]
            )
            ->sortByDesc('total')
            ->take(5)
            ->values();

        $selectedLetter = $this->selectedLetterId
            ? $this->baseQuery()
                ->with(['activityType', 'personnels.unit', 'attachments', 'letterType'])
                ->find($this->selectedLetterId)
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
            'years' => $this->baseQuery()
                ->whereNotNull('letter_date')
                ->orderByDesc('letter_date')
                ->get(['letter_date'])
                ->pluck('letter_date')
                ->map(fn ($date) => (int) $date->format('Y'))
                ->unique()
                ->values(),
            'selectedLetter' => $selectedLetter,
        ]);
    }
}
