<?php

namespace App\Livewire\MySpt;

use App\Enums\LetterStatus;
use App\Models\Letter;
use App\Support\PersonalLetterAccess;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    public Letter $letter;

    public function mount(
        Letter $letter,
        PersonalLetterAccess $access
    ): void {
        Gate::authorize('my-letters.view');

        $letter->loadMissing('letterType');

        abort_unless(
            $letter->letterType?->code === 'SPT',
            404
        );

        abort_if(
            in_array(
                $letter->status,
                [
                    LetterStatus::Draft,
                    LetterStatus::Cancelled,
                ],
                true
            ),
            404
        );

        abort_unless(
            $access->canAccess(
                auth()->user(),
                $letter
            ),
            404
        );

        $this->letter = $letter->load([
            'letterType',
            'activityType',
            'personnels.unit',
        ]);
    }

    public function render()
    {
        return view(
            'livewire.my-spt.show'
        );
    }
}
