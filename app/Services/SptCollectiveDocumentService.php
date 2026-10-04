<?php

namespace App\Services;

use App\Models\OutgoingLetter;
use App\Models\IssuedLetter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SptCollectiveDocumentService
{
    public const TEMPLATE_CODE = 'SPT_SYSTEM_COLLECTIVE_V2';

    public function isCollective(OutgoingLetter $letter): bool
    {
        $letter->loadMissing('template');
        return $letter->template?->code === self::TEMPLATE_CODE;
    }

    /** Generate once at publish time; never regenerate already issued PDFs. */
    public function renderAnnex(OutgoingLetter $letter, IssuedLetter $issued, string $verificationUrl, string $qrDataUri): ?string
    {
        if (! $this->isCollective($letter)) {
            return null;
        }

        $letter->loadMissing('personnels');
        if ($letter->personnels->isEmpty()) {
            throw ValidationException::withMessages(['personnel_ids' => 'SPT kolektif wajib memiliki personil untuk lampiran.']);
        }

        $people = $letter->personnels->sortBy('name')->values()->map(function ($p) {
            return [
                'name' => $p->name,
                'nip' => $p->nip,
                'rank' => $p->rank,
                'grade' => $p->grade,
                'position' => $p->position,
            ];
        })->all();

        return Pdf::loadView('issued-letters.spt-collective-annex', [
            'issued' => $issued,
            'letter' => $letter,
            'people' => $people,
            'verificationUrl' => $verificationUrl,
            'qrDataUri' => $qrDataUri,
        ])->setPaper('a4', 'portrait')->output();
    }

    public function archivedAnnexBinary(IssuedLetter $issued): string
    {
        if (! $issued->annex_pdf_path || ! Storage::exists($issued->annex_pdf_path)) {
            throw ValidationException::withMessages(['document' => 'Arsip lampiran resmi tidak tersedia.']);
        }
        $binary = Storage::get($issued->annex_pdf_path);
        if (! $issued->annex_file_sha256 || ! hash_equals($issued->annex_file_sha256, hash('sha256', $binary))) {
            throw ValidationException::withMessages(['document' => 'Integritas lampiran tidak valid.']);
        }
        return $binary;
    }
}
