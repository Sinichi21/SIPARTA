<?php

namespace App\Livewire\IssuedLetters;

use App\Models\IssuedLetter;
use App\Services\IssuedLetterRevocationService;
use App\Services\IssuedLetterVerificationService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Show extends Component
{
    public IssuedLetter $letter;

    public string $revocationReason = '';

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
                'revoker',
                'outgoingLetter.letterheadProfile',
            ]);
    }

    public function revoke(
        IssuedLetterRevocationService $service
    ): void {
        Gate::authorize('issued-letters.revoke');

        $this->validate([
            'revocationReason' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ]);

        $this->letter = $service->revoke(
            $this->letter,
            auth()->user(),
            $this->revocationReason
        );

        $this->revocationReason = '';

        session()->flash(
            'success',
            'Dokumen resmi berhasil dicabut. Arsip PDF tetap dipertahankan untuk riwayat.'
        );
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
