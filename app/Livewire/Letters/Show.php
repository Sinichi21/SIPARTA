<?php

use App\Enums\LetterStatus;
use App\Models\Letter;
use App\Services\LetterService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component {
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

        $service->publish(
            $this->letter,
            Auth::id()
        );

        $this->letter->refresh();

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

        $service->cancel(
            $this->letter,
            $this->cancellationReason,
            Auth::id()
        );

        $this->letter->refresh();

        session()->flash(
            'success',
            'SPT berhasil dibatalkan.'
        );
    }
};