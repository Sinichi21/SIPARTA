<?php

namespace App\Livewire\IssuedLetters;

use App\Models\IssuedLetter;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $year = '';

    public function mount(): void
    {
        Gate::authorize('issued-letters.view');
        $this->year = (string) now()->year;
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.issued-letters.index', [
            'letters' => IssuedLetter::query()
                ->with(['letterType', 'issuer'])
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
                    filled($this->year),
                    fn ($query) => $query->whereYear('letter_date', $this->year)
                )
                ->latest('issued_at')
                ->paginate(15),

            'total' => IssuedLetter::query()->count(),
            'thisYear' => IssuedLetter::query()
                ->whereYear('letter_date', now()->year)
                ->count(),
            'thisMonth' => IssuedLetter::query()
                ->whereYear('letter_date', now()->year)
                ->whereMonth('letter_date', now()->month)
                ->count(),
        ]);
    }
}
