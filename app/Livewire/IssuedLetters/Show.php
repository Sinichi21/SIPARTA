<?php

namespace App\Livewire\IssuedLetters;

use App\Models\IssuedLetter;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    public IssuedLetter $letter;

    public function mount(IssuedLetter $letter): void
    {
        Gate::authorize('issued-letters.view');

        $this->letter = $letter->load([
            'letterType',
            'issuer',
            'outgoingLetter',
        ]);
    }

    public function render()
    {
        return view('livewire.issued-letters.show');
    }
}
