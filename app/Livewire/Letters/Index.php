<?php

namespace App\Livewire\Letters;

use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\Personnel;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[\Livewire\Attributes\Url]
    public string $search = '';
    public string $status = '';
    public string $month = '';
    public string $activityType = '';
    public string $personnel = '';
    public string $recordType = 'normal';

    public function mount(): void
    {
        Gate::authorize('letters.view');
        $this->year = (string) now()->year;
    }

    public function updated($property): void
    {
        if (in_array($property, [
            'search',
            'status',
            'year',
            'month',
            'activityType',
            'personnel',
            'recordType',
        ], true)) {
            $this->resetPage();
        }
    }

    public function render()
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
                            ->where(
                                'number',
                                'ilike',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'subject',
                                'ilike',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'location',
                                'ilike',
                                "%{$search}%"
                            )
                            ->orWhereHas(
                                'personnels',
                                fn ($personnelQuery) =>
                                    $personnelQuery->where(
                                        'name',
                                        'ilike',
                                        "%{$search}%"
                                    )
                            );
                    });
                }
            )
            ->when(
                filled($this->status),
                fn ($query) => $query->where('status', $this->status)
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
                    fn ($personnelQuery) => $personnelQuery->where(
                        'personnels.id',
                        $this->personnel
                    )
                )
            )
            ->when(
                $this->recordType !== 'all',
                fn ($query) => $query->where(
                    'record_type',
                    $this->recordType
                )
            )
            ->orderByDesc('letter_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.letters.index', [
            'letters' => $letters,
            'activityTypes' => ActivityType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'personnels' => Personnel::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'years' => Letter::query()
                ->whereHas(
                    'letterType',
                    fn ($query) =>
                        $query->where('code', 'SPT')
                )
                ->whereNotNull('letter_date')
                ->selectRaw(
                    'EXTRACT(YEAR FROM letter_date)::int as year'
                )
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year'),
        ]);
    }
}
