<?php

namespace App\Livewire\MySpt;

use App\Models\Letter;
use App\Support\PersonalLetterAccess;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $year = '';

    public function mount(): void
    {
        Gate::authorize('my-letters.view');

        if (! request()->has('year')) {
            $this->year = (string) now()->year;
        }
    }

    public function updated(
        string $property
    ): void {
        if (
            in_array(
                $property,
                ['search', 'year'],
                true
            )
        ) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->year = (string) now()->year;
        $this->resetPage();
    }

    public function render(
        PersonalLetterAccess $access
    ) {
        $query = Letter::query()
            ->spt()
            ->with([
                'activityType',
                'personnels.unit',
            ])
            ->whereNotIn(
                'status',
                ['draft', 'cancelled']
            );

        $access->apply(
            $query,
            auth()->user()
        );

        $letters = $query
            ->when(
                filled($this->search),
                function ($query) {
                    $search =
                        trim($this->search);

                    $query->where(
                        function ($query) use ($search) {
                            $query
                                ->whereLike(
                                    'number',
                                    "%{$search}%"
                                )
                                ->orWhereLike(
                                    'subject',
                                    "%{$search}%"
                                )
                                ->orWhereLike(
                                    'location',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                filled($this->year),
                fn ($query) => $query->whereYear(
                    'letter_date',
                    (int) $this->year
                )
            )
            ->orderByDesc('letter_date')
            ->orderByDesc('id')
            ->paginate(15);

        $yearQuery = Letter::query()
            ->spt()
            ->whereNotNull('letter_date')
            ->whereNotIn(
                'status',
                ['draft', 'cancelled']
            );

        $access->apply(
            $yearQuery,
            auth()->user()
        );

        return view(
            'livewire.my-spt.index',
            [
                'letters' => $letters,
                'years' => $yearQuery
                    ->orderByDesc('letter_date')
                    ->get(['letter_date'])
                    ->pluck('letter_date')
                    ->map(
                        fn ($date) => (int) $date->format('Y')
                    )
                    ->unique()
                    ->values(),
            ]
        );
    }
}
