<?php

namespace App\Http\Controllers;

use App\Models\IssuedLetter;
use App\Services\SptCollectiveDocumentService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class IssuedSptAnnexController extends Controller
{
    public function __invoke(IssuedLetter $letter, SptCollectiveDocumentService $annex): Response
    {
        Gate::authorize('issued-letters.view');
        abort_unless($letter->annex_pdf_path, 404);
        return response($annex->archivedAnnexBinary($letter), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.($letter->annex_pdf_name ?: 'lampiran-spt.pdf').'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
