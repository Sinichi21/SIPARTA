<?php

namespace App\Http\Controllers;

use App\Services\IssuedLetterVerificationService;

class IssuedLetterVerificationController extends Controller
{
    public function __invoke(
        string $code,
        IssuedLetterVerificationService $verification
    ) {
        $issued = $verification->findPublic($code);

        return view('verification.issued-letter', [
            'issued' => $issued,
        ]);
    }
}
