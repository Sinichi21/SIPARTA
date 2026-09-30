<?php

namespace App\Livewire;

use App\Enums\LetterStatus;
use App\Models\AuditLog;
use App\Models\Letter;
use App\Models\Personnel;
use App\Support\PersonalLetterAccess;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('dashboard.view')
            || auth()->user()?->can('my-dashboard.view'),
            403
        );
    }

    public function render()
    {
        $personalAccess =
            app(PersonalLetterAccess::class);

        if (
            auth()->user()->can('my-dashboard.view')
            && ! auth()->user()->can('letters.view')
        ) {
            return $this->renderPersonal(
                $personalAccess
            );
        }

        $sptQuery = Letter::query()
            ->whereHas(
                'letterType',
                fn ($query) =>
                    $query->where('code', 'SPT')
            );

        return view('livewire.dashboard', [
            'scheduledLetters' =>
                auth()->user()->can('letters.view')
                    ? (clone $sptQuery)
                        ->with('activityType')
                        ->where(
                            'record_type',
                            'normal'
                        )
                        ->where(
                            'status',
                            LetterStatus::Published->value
                        )
                        ->where(
                            function ($query) {
                                $query
                                    ->whereDate(
                                        'end_date',
                                        '>=',
                                        today()
                                    )
                                    ->orWhere(
                                        function ($query) {
                                            $query
                                                ->whereNull('end_date')
                                                ->whereDate(
                                                    'start_date',
                                                    '>=',
                                                    today()
                                                );
                                        }
                                    );
                            }
                        )
                        ->orderBy('start_date')
                        ->limit(3)
                        ->get()
                    : collect(),

            'recentActivities' =>
                auth()->user()->can('audit-logs.view')
                    ? AuditLog::with('user')
                        ->latest('created_at')
                        ->limit(4)
                        ->get()
                    : collect(),

            'archivedSpt' =>
                (clone $sptQuery)
                    ->where(
                        'status',
                        LetterStatus::Archived->value
                    )
                    ->count(),

            'totalSpt' =>
                (clone $sptQuery)->count(),

            'monthlySpt' =>
                (clone $sptQuery)
                    ->whereYear(
                        'letter_date',
                        now()->year
                    )
                    ->whereMonth(
                        'letter_date',
                        now()->month
                    )
                    ->count(),

            'draftSpt' =>
                (clone $sptQuery)
                    ->where(
                        'status',
                        LetterStatus::Draft->value
                    )
                    ->count(),

            'publishedSpt' =>
                (clone $sptQuery)
                    ->where(
                        'status',
                        LetterStatus::Published->value
                    )
                    ->count(),

            'activePersonnel' =>
                Personnel::query()
                    ->where(
                        'is_active',
                        true
                    )
                    ->count(),

            'latestLetters' =>
                Letter::query()
                    ->with([
                        'activityType',
                        'personnels',
                    ])
                    ->whereHas(
                        'letterType',
                        fn ($query) =>
                            $query->where(
                                'code',
                                'SPT'
                            )
                    )
                    ->latest('letter_date')
                    ->latest('id')
                    ->limit(5)
                    ->get(),
        ]);
    }

    private function renderPersonal(
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

        return view(
            'livewire.dashboard-personal',
            [
                'personalTotalSpt' =>
                    (clone $base)->count(),

                'personalYearSpt' =>
                    (clone $base)
                        ->whereYear(
                            'letter_date',
                            now()->year
                        )
                        ->count(),

                'personalPublishedSpt' =>
                    (clone $base)
                        ->where(
                            'status',
                            'published'
                        )
                        ->count(),

                'personalUpcomingSpt' =>
                    (clone $base)
                        ->whereDate(
                            'start_date',
                            '>',
                            today()
                        )
                        ->count(),

                'personalLatestSpt' =>
                    (clone $base)
                        ->with('activityType')
                        ->orderByDesc(
                            'letter_date'
                        )
                        ->orderByDesc('id')
                        ->limit(5)
                        ->get(),
            ]
        );
    }
}
