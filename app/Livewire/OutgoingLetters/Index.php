<?php

namespace App\Livewire\OutgoingLetters;

use App\Models\OutgoingLetter;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $year = '';

    public function mount(): void
    {
        Gate::authorize('outgoing-letters.view');
        $this->year = (string) now()->year;
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.outgoing-letters.index', [
            'letters' => OutgoingLetter::query()
                ->with('letterType')
                ->when(
                    filled($this->search),
                    function ($query) {
                        $search = trim($this->search);

                        $query->where(function ($query) use ($search) {
                            $query
                                ->whereLike('number', "%{$search}%")
                                ->orWhereLike('recipient', "%{$search}%")
                                ->orWhereLike('subject', "%{$search}%");
                        });
                    }
                )
                ->when(
                    filled($this->status),
                    fn ($query) => $query->where('status', $this->status)
                )
                ->when(
                    filled($this->year),
                    fn ($query) => $query->whereYear('created_at', $this->year)
                )
                ->latest('created_at')
                ->paginate(15),

            'draft' => OutgoingLetter::query()->where('status', 'draft')->count(),
            'waitingApproval' => OutgoingLetter::query()
                ->whereIn('status', ['verified', 'approved', 'numbered'])
                ->count(),
            'published' => OutgoingLetter::query()
                ->where('status', 'published')
                ->count(),
            'sent' => OutgoingLetter::query()
                ->whereIn('status', ['sent', 'archived'])
                ->count(),
        ]);
    }
}
