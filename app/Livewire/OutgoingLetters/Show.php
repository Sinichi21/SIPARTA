<?php

namespace App\Livewire\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Services\OutgoingLetterService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    public OutgoingLetter $letter;

    public string $number = '';
    public string $letter_date = '';

    public function mount(OutgoingLetter $letter): void
    {
        Gate::authorize('outgoing-letters.view');

        $this->letter = $letter->load([
            'letterType',
            'template',
            'letterheadProfile',
            'creator',
            'verifier',
            'approver',
            'issuer',
            'issuedLetter',
        ]);

        $this->number = (string) ($letter->number ?? '');
        $this->letter_date = $letter->letter_date?->toDateString()
            ?? now()->toDateString();
    }

    public function verify(OutgoingLetterService $service): void
    {
        Gate::authorize('outgoing-letters.verify');
        $service->verify($this->letter, auth()->user());
        $this->refreshLetter();
    }

    public function approve(OutgoingLetterService $service): void
    {
        Gate::authorize('outgoing-letters.approve');
        $service->approve($this->letter, auth()->user());
        $this->refreshLetter();
    }

    public function assignNumber(OutgoingLetterService $service): void
    {
        Gate::authorize('outgoing-letters.number');
        $service->number(
            $this->letter,
            auth()->user(),
            $this->number,
            $this->letter_date
        );
        $this->refreshLetter();
    }

    public function publish(OutgoingLetterService $service): void
    {
        Gate::authorize('outgoing-letters.publish');
        $service->publish($this->letter, auth()->user());
        $this->refreshLetter();

        session()->flash(
            'success',
            'Surat berhasil diterbitkan dan masuk Register Surat Terbit.'
        );
    }

    public function send(OutgoingLetterService $service): void
    {
        Gate::authorize('outgoing-letters.send');
        $service->send($this->letter, auth()->user());
        $this->refreshLetter();
    }

    public function archive(OutgoingLetterService $service): void
    {
        Gate::authorize('outgoing-letters.archive');
        $service->archive($this->letter, auth()->user());
        $this->refreshLetter();
    }

    private function refreshLetter(): void
    {
        $this->letter->refresh()->load([
            'letterType',
            'template',
            'letterheadProfile',
            'creator',
            'verifier',
            'approver',
            'issuer',
            'issuedLetter',
        ]);
    }

    public function render()
    {
        return view('livewire.outgoing-letters.show');
    }
}
