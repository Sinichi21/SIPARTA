<?php

namespace App\Http\Controllers;

use App\Models\OutgoingLetter;
use App\Services\OutgoingLetterTemplateRenderer;
use Illuminate\Support\Facades\Gate;

class OutgoingLetterDocumentController extends Controller
{
    public function preview(
        OutgoingLetter $letter,
        OutgoingLetterTemplateRenderer $renderer
    ) {
        return $this->viewDocument(
            $letter,
            $renderer,
            false
        );
    }

    public function print(
        OutgoingLetter $letter,
        OutgoingLetterTemplateRenderer $renderer
    ) {
        return $this->viewDocument(
            $letter,
            $renderer,
            true
        );
    }

    private function viewDocument(
        OutgoingLetter $letter,
        OutgoingLetterTemplateRenderer $renderer,
        bool $autoPrint
    ) {
        Gate::authorize(
            'outgoing-letters.view'
        );

        $letter->load([
            'letterType',
            'template',
            'letterheadProfile',
        ]);

        return view(
            'outgoing-letters.document-preview',
            [
                'letter' => $letter,
                'rendered' =>
                    $renderer->render(
                        $letter
                    ),
                'missingPlaceholders' =>
                    $renderer
                        ->missingPlaceholders(
                            $letter
                        ),
                'autoPrint' =>
                    $autoPrint,
            ]
        );
    }
}
