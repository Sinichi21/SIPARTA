<?php

namespace App\Livewire;

use App\Enums\LetterStatus;
use App\Models\AuditLog;
use App\Models\IncomingLetter;
use App\Models\IssuedLetter;
use App\Models\Letter;
use App\Models\OutgoingLetter;
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
        $personalAccess = app(PersonalLetterAccess::class);

        if (
            auth()->user()->can('my-dashboard.view')
            && ! auth()->user()->can('letters.view')
        ) {
            return $this->renderPersonal($personalAccess);
        }

        $sptQuery = Letter::query()
            ->whereHas(
                'letterType',
                fn ($query) => $query->where('code', 'SPT')
            );

        $incomingActive = auth()->user()->can('incoming-letters.view')
            ? IncomingLetter::query()
                ->whereIn('status', ['recorded', 'disposed', 'processing'])
                ->count()
            : 0;

        $outgoingPending = auth()->user()->can('outgoing-letters.view')
            ? OutgoingLetter::query()
                ->whereIn('status', ['draft', 'verified', 'approved', 'numbered'])
                ->count()
            : 0;

        $issuedThisMonth = auth()->user()->can('issued-letters.view')
            ? IssuedLetter::query()
                ->whereYear('letter_date', now()->year)
                ->whereMonth('letter_date', now()->month)
                ->count()
            : 0;

        $revokedLetters = auth()->user()->can('issued-letters.view')
            ? IssuedLetter::query()
                ->where('status', 'revoked')
                ->count()
            : 0;

        return view('livewire.dashboard', [
            'scheduledLetters' =>
                auth()->user()->can('letters.view')
                    ? (clone $sptQuery)
                        ->with('activityType')
                        ->where('record_type', 'normal')
                        ->where('status', LetterStatus::Published->value)
                        ->where(function ($query) {
                            $query
                                ->whereDate('end_date', '>=', today())
                                ->orWhere(function ($query) {
                                    $query
                                        ->whereNull('end_date')
                                        ->whereDate('start_date', '>=', today());
                                });
                        })
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

            'archivedSpt' => (clone $sptQuery)
                ->where('status', LetterStatus::Archived->value)
                ->count(),

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
                ->with(['activityType', 'personnels'])
                ->whereHas(
                    'letterType',
                    fn ($query) => $query->where('code', 'SPT')
                )
                ->latest('letter_date')
                ->latest('id')
                ->limit(5)
                ->get(),

            'incomingActive' => $incomingActive,
            'outgoingPending' => $outgoingPending,
            'issuedThisMonth' => $issuedThisMonth,
            'revokedLetters' => $revokedLetters,

            'incomingQueue' =>
                auth()->user()->can('incoming-letters.view')
                    ? IncomingLetter::query()
                        ->whereIn('status', ['recorded', 'disposed', 'processing'])
                        ->orderBy('received_date')
                        ->orderBy('id')
                        ->limit(5)
                        ->get()
                    : collect(),

            'outgoingQueue' =>
                auth()->user()->can('outgoing-letters.view')
                    ? OutgoingLetter::query()
                        ->with('letterType')
                        ->whereIn('status', ['draft', 'verified', 'approved', 'numbered'])
                        ->orderBy('created_at')
                        ->orderBy('id')
                        ->limit(5)
                        ->get()
                    : collect(),

            'recentIssued' =>
                auth()->user()->can('issued-letters.view')
                    ? IssuedLetter::query()
                        ->with('letterType')
                        ->latest('issued_at')
                        ->limit(5)
                        ->get()
                    : collect(),
        ]);
    }

    private function renderPersonal(PersonalLetterAccess $access)
    {
        $base = Letter::query()
            ->spt()
            ->whereNotIn('status', ['draft', 'cancelled']);

        $access->apply($base, auth()->user());

        return view(
            'livewire.dashboard-personal',
            [
                'personalTotalSpt' => (clone $base)->count(),

                'personalYearSpt' => (clone $base)
                    ->whereYear('letter_date', now()->year)
                    ->count(),

                'personalPublishedSpt' => (clone $base)
                    ->where('status', 'published')
                    ->count(),

                'personalUpcomingSpt' => (clone $base)
                    ->whereDate('start_date', '>', today())
                    ->count(),

                'personalLatestSpt' => (clone $base)
                    ->with('activityType')
                    ->orderByDesc('letter_date')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get(),
            ]
        );
    }
}
