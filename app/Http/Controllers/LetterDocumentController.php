<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Services\IssuedLetterVerificationService;
use App\Services\LetterDocumentService;
use App\Services\OutgoingLetterTemplateRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class LetterDocumentController extends Controller
{
    public function preview(Letter $letter, LetterDocumentService $documents)
    {
        if ($response = $this->linkedDocument($letter, 'preview')) {
            return $response;
        }

        return view('letters.document-preview', [
            'letter' => $letter,
            'payload' => $this->payload($letter, $documents),
            'autoPrint' => false,
        ]);
    }

    public function print(Letter $letter, LetterDocumentService $documents)
    {
        if ($response = $this->linkedDocument($letter, 'print')) {
            return $response;
        }

        return view('letters.document-preview', [
            'letter' => $letter,
            'payload' => $this->payload($letter, $documents),
            'autoPrint' => true,
        ]);
    }

    public function pdf(Letter $letter, LetterDocumentService $documents): Response
    {
        if ($response = $this->linkedDocument($letter, 'pdf')) {
            return $response;
        }

        $payload = $this->payload($letter, $documents);
        $pdf = Pdf::loadView('letters.document-pdf', ['payload' => $payload])
            ->setPaper('a4', 'portrait');

        $number = $payload['letter']['number'] ?: 'draft-'.$letter->id;

        return $pdf->download('SPT-'.Str::slug($number).'.pdf');
    }

    private function linkedDocument(Letter $letter, string $mode)
    {
        Gate::authorize('letters.view');
        abort_unless($letter->letterType?->code === 'SPT', 404);
        $outgoing = $letter->outgoingLetter()->with('issuedLetter')->first();

        if (! $outgoing) {
            return null;
        }

        if ($issued = $outgoing->issuedLetter) {
            // Always serve the immutable archive, including its original personnel table.
            // Missing or invalid archives must not silently become a newly generated letter.
            $binary = app(IssuedLetterVerificationService::class)->archivedPdfBinary($issued);

            if ($mode === 'pdf') {
                $filename = 'SPT-'.Str::slug($issued->number ?: (string) $issued->id).'.pdf';

                return response($binary, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => (request()->boolean('inline') ? 'inline' : 'attachment').'; filename="'.$filename.'"',
                    'Cache-Control' => 'private, no-store',
                ]);
            }

            return view('letters.linked-document-preview', [
                'letter' => $letter,
                'issued' => $issued,
                'autoPrint' => $mode === 'print',
            ]);
        }

        $outgoing->loadMissing(['letterheadProfile', 'template', 'letterType']);
        $renderer = app(OutgoingLetterTemplateRenderer::class);
        $data = [
            'letter' => $outgoing,
            'rendered' => $renderer->render($outgoing),
            'missingPlaceholders' => $renderer->missingPlaceholders($outgoing),
            'autoPrint' => $mode === 'print',
            'forPdf' => $mode === 'pdf',
            'backUrl' => route('letters.show', $letter),
            'downloadUrl' => route('letters.document.pdf', $letter),
        ];

        if ($mode === 'pdf') {
            return Pdf::loadView('outgoing-letters.document-preview', $data)
                ->setPaper('a4', 'portrait')
                ->download('SPT-draft-'.$outgoing->id.'.pdf');
        }

        return view('outgoing-letters.document-preview', $data);
    }

    private function payload(Letter $letter, LetterDocumentService $documents): array
    {
        Gate::authorize('letters.view');
        abort_unless($letter->letterType?->code === 'SPT', 404);

        return $documents->payloadFor($letter, auth()->id());
    }
}
