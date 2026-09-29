<?php

namespace App\Livewire\Letters;

use App\Models\Letter;
use App\Services\LetterService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    public Letter $letter;
    public string $cancellationReason = '';

    public function mount(Letter $letter): void
    {
        Gate::authorize('letters.view');

        abort_unless(
            $letter->letterType?->code === 'SPT',
            404
        );

        $this->letter = $letter->load([
            'letterType',
            'activityType',
            'personnels.unit',
            'creator',
            'updater',
            'canceller',
        ]);
    }

    public function publish(LetterService $service): void
    {
        Gate::authorize('letters.publish');

        $this->letter = $service
            ->publish($this->letter, Auth::id())
            ->load([
                'letterType',
                'activityType',
                'personnels.unit',
                'creator',
                'updater',
                'canceller',
            ]);

        session()->flash(
            'success',
            'SPT berhasil diterbitkan.'
        );
    }

    public function cancel(LetterService $service): void
    {
        Gate::authorize('letters.cancel');

        $this->validate([
            'cancellationReason' => [
                'required',
                'string',
                'min:5',
                'max:1000',
            ],
        ]);

        $this->letter = $service
            ->cancel(
                $this->letter,
                $this->cancellationReason,
                Auth::id()
            )
            ->load([
                'letterType',
                'activityType',
                'personnels.unit',
                'creator',
                'updater',
                'canceller',
            ]);

        $this->cancellationReason = '';

        session()->flash(
            'success',
            'SPT berhasil dibatalkan.'
        );
    }

    public function render()
    {
        return view('livewire.letters.show');
    }
}
