<?php

namespace App\Livewire\MyRecap;

use App\Models\Letter;
use App\Support\PersonalLetterAccess;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    public string $year = '';

    public function mount(): void
    {
        Gate::authorize('my-reports.view');

        $this->year = (string) now()->year;
    }

    public function render(
        PersonalLetterAccess $access
    ) {
        $base = Letter::query()
            ->spt()
            ->whereNotIn(
                'status',
                ['draft', 'cancelled']
            );

        $access->apply(
            $base,
            auth()->user()
        );

        $filtered = (clone $base)
            ->when(
                filled($this->year),
                fn ($query) =>
                    $query->whereYear(
                        'letter_date',
                        (int) $this->year
                    )
            );

        $history = (clone $filtered)
            ->with('activityType')
            ->orderByDesc('letter_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $years = (clone $base)
            ->whereNotNull('letter_date')
            ->orderByDesc('letter_date')
            ->get(['letter_date'])
            ->pluck('letter_date')
            ->map(
                fn ($date) =>
                    (int) $date->format('Y')
            )
            ->unique()
            ->values();

        return view(
            'livewire.my-recap.index',
            [
                'total' =>
                    (clone $filtered)->count(),

                'published' =>
                    (clone $filtered)
                        ->where(
                            'status',
                            'published'
                        )
                        ->count(),

                'latest' =>
                    (clone $filtered)
                        ->orderByDesc('letter_date')
                        ->orderByDesc('id')
                        ->first(),

                'history' => $history,
                'years' => $years,
            ]
        );
    }
}
