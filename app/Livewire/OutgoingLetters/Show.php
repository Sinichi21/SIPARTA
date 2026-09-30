<?php

namespace App\Livewire\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Services\OutgoingLetterService;
use App\Services\OutgoingLetterTemplateRenderer;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    public OutgoingLetter $letter;

    public function mount(OutgoingLetter $letter): void
    {
        Gate::authorize('outgoing-letters.view');
        $this->letter = $letter;
        $this->refreshLetter();
    }

    public function verify(OutgoingLetterService $service): void
    {
        Gate::authorize('outgoing-letters.verify');
        $service->verify($this->letter, auth()->user());
        $this->refreshLetter();

        session()->flash(
            'success',
            'Surat berhasil diverifikasi.'
        );
    }

    public function approve(OutgoingLetterService $service): void
    {
        Gate::authorize('outgoing-letters.approve');
        $service->approve($this->letter, auth()->user());
        $this->refreshLetter();

        session()->flash(
            'success',
            'Surat berhasil disetujui.'
        );
    }

    public function publish(OutgoingLetterService $service): void
    {
        Gate::authorize('outgoing-letters.publish');
        $service->publish($this->letter, auth()->user());
        $this->refreshLetter();

        session()->flash(
            'success',
            'Surat berhasil diterbitkan. Nomor resmi dialokasikan saat penerbitan.'
        );
    }

    public function send(OutgoingLetterService $service): void
    {
        Gate::authorize('outgoing-letters.send');
        $service->send($this->letter, auth()->user());
        $this->refreshLetter();

        session()->flash(
            'success',
            'Surat berhasil ditandai sebagai dikirim.'
        );
    }

    public function archive(OutgoingLetterService $service): void
    {
        Gate::authorize('outgoing-letters.archive');
        $service->archive($this->letter, auth()->user());
        $this->refreshLetter();

        session()->flash(
            'success',
            'Surat berhasil diarsipkan.'
        );
    }

    public function render(OutgoingLetterTemplateRenderer $renderer)
    {
        return view('livewire.outgoing-letters.show', [
            'previewBody' => $renderer->render(
                $this->letter,
                ! in_array(
                    $this->letter->status,
                    [
                        OutgoingLetterStatus::Published,
                        OutgoingLetterStatus::Sent,
                        OutgoingLetterStatus::Archived,
                    ],
                    true
                ),
                true
            ),
            'missingPlaceholders' => $renderer->missingPlaceholders(
                $this->letter
            ),
        ]);
    }

    private function refreshLetter(): void
    {
        $this->letter
            ->refresh()
            ->load([
                'letterType',
                'template',
                'letterheadProfile',
                'creator',
                'verifier',
                'approver',
                'issuer',
                'issuedLetter',
                'sourceSpt.activityType',
                'personnels',
            ]);
    }
}
