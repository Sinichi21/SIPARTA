<?php

namespace App\Livewire;

use App\Enums\LetterStatus;
use App\Models\Letter;
use App\Models\Personnel;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): void
    {
        Gate::authorize('dashboard.view');
    }

    public function render()
    {
        $sptQuery = Letter::query()
            ->whereHas(
                'letterType',
                fn ($query) => $query->where('code', 'SPT')
            );

        return view('livewire.dashboard', [
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
        ]);
    }
}