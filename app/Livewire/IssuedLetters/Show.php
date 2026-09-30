<?php

namespace App\Livewire\IssuedLetters;

use App\Models\IssuedLetter;
use App\Services\IssuedLetterVerificationService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Show extends Component
{
    public IssuedLetter $letter;

    public function mount(
        IssuedLetter $letter,
        IssuedLetterVerificationService $verification
    ): void {
        Gate::authorize('issued-letters.view');

        $this->letter = $verification
            ->ensureCode($letter)
            ->load([
                'letterType',
                'issuer',
                'outgoingLetter.letterheadProfile',
            ]);
    }

    public function downloadArchivedPdf(
        IssuedLetterVerificationService $verification
    ): StreamedResponse {
        Gate::authorize('issued-letters.view');

        $binary = $verification
            ->archivedPdfBinary($this->letter);

        $filename = $this->letter->pdf_name
            ?: 'surat-'.$this->letter->id.'.pdf';

        return response()->streamDownload(
            function () use ($binary): void {
                echo $binary;
            },
            $filename,
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }

    public function render(
        IssuedLetterVerificationService $verification
    ) {
        return view('livewire.issued-letters.show', [
            'verificationUrl' => $verification->publicUrl(
                $this->letter
            ),
            'qrDataUri' => $verification->qrDataUri(
                $this->letter
            ),
        ]);
    }
}
