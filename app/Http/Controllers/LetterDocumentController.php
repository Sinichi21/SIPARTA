<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Services\LetterDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class LetterDocumentController extends Controller
{
    public function preview(Letter $letter, LetterDocumentService $documents)
    {
        return view('letters.document-preview', [
            'letter' => $letter,
            'payload' => $this->payload($letter, $documents),
            'autoPrint' => false,
        ]);
    }

    public function print(Letter $letter, LetterDocumentService $documents)
    {
        return view('letters.document-preview', [
            'letter' => $letter,
            'payload' => $this->payload($letter, $documents),
            'autoPrint' => true,
        ]);
    }

    public function pdf(Letter $letter, LetterDocumentService $documents): Response
    {
        $payload = $this->payload($letter, $documents);
        $pdf = Pdf::loadView('letters.document-pdf', ['payload' => $payload])
            ->setPaper('a4', 'portrait');

        $number = $payload['letter']['number'] ?: 'draft-'.$letter->id;

        return $pdf->download('SPT-'.Str::slug($number).'.pdf');
    }

    private function payload(Letter $letter, LetterDocumentService $documents): array
    {
        Gate::authorize('letters.view');
        abort_unless($letter->letterType?->code === 'SPT', 404);

        return $documents->payloadFor($letter, auth()->id());
    }
}
