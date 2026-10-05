<?php

namespace App\Http\Controllers;

use App\Models\SptReportAttachment;
use App\Support\PersonalLetterAccess;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SptReportAttachmentController extends Controller
{
    public function __invoke(
        SptReportAttachment $attachment,
        PersonalLetterAccess $access
    ): StreamedResponse {
        Gate::authorize('my-reports.view');

        $attachment->loadMissing('report.letter');
        $letter = $attachment->report?->letter;

        abort_unless(
            $letter && $access->canAccess(auth()->user(), $letter),
            404
        );

        abort_unless(
            Storage::disk('local')->exists($attachment->path),
            404
        );

        return Storage::disk('local')->download(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => 'application/pdf']
        );
    }
}
