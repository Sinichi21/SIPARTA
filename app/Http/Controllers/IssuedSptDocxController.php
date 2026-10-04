<?php

namespace App\Http\Controllers;

use App\Models\IssuedLetter;
use App\Services\IssuedSptDocxExportService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class IssuedSptDocxController extends Controller
{
    public function __invoke(IssuedLetter $letter, IssuedSptDocxExportService $service): Response
    {
        Gate::authorize('issued-letters.view');
        $export = $service->export($letter);
        return response($export['binary'], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$export['filename'].'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
