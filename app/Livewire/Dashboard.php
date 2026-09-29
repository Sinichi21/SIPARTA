<?php

namespace App\Livewire;

use App\Enums\LetterStatus;
use App\Models\AuditLog;
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
            'scheduledLetters' => auth()->user()->can('letters.view')
                ? (clone $sptQuery)->with('activityType')
                    ->where('record_type', 'normal')
                    ->where('status', LetterStatus::Published->value)
                    ->where(function ($query) {
                        $query->whereDate('end_date', '>=', today())
                            ->orWhere(function ($query) {
                                $query->whereNull('end_date')->whereDate('start_date', '>=', today());
                            });
                    })
                    ->orderBy('start_date')->limit(3)->get()
                : collect(),
            'recentActivities' => auth()->user()->can('audit-logs.view')
                ? AuditLog::with('user')->latest('created_at')->limit(4)->get()
                : collect(),
            'archivedSpt' => (clone $sptQuery)->where('status', LetterStatus::Archived->value)->count(),
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
                ->limit(5)
                ->get(),
        ]);
    }
}
