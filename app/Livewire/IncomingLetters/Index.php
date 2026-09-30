<?php

namespace App\Livewire\IncomingLetters;

use App\Models\IncomingLetter;
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
        Gate::authorize('incoming-letters.view');
        $this->year = (string) now()->year;
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.incoming-letters.index', [
            'letters' => IncomingLetter::query()
                ->when(
                    filled($this->search),
                    function ($query) {
                        $search = trim($this->search);

                        $query->where(function ($query) use ($search) {
                            $query
                                ->whereLike('agenda_number', "%{$search}%")
                                ->orWhereLike('number', "%{$search}%")
                                ->orWhereLike('sender', "%{$search}%")
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
                    fn ($query) => $query->whereYear('received_date', $this->year)
                )
                ->latest('received_date')
                ->latest('id')
                ->paginate(15),

            'total' => IncomingLetter::query()->count(),
            'processing' => IncomingLetter::query()
                ->where('status', 'processing')
                ->count(),
            'completed' => IncomingLetter::query()
                ->where('status', 'completed')
                ->count(),
            'archived' => IncomingLetter::query()
                ->where('status', 'archived')
                ->count(),
        ]);
    }
}
