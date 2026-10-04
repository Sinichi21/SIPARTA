<?php

namespace App\Services;

use App\Models\IssuedLetter;
use App\Models\OutgoingLetter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class IssuedLetterArchiveService
{
    public function __construct(
        private readonly OutgoingLetterTemplateRenderer $renderer,
        private readonly IssuedLetterVerificationService $verification,
    ) {}

    public function archive(
        IssuedLetter $issued,
        OutgoingLetter $letter
    ): IssuedLetter {
        $letter->loadMissing([
            'letterType',
            'template',
            'letterheadProfile',
            'issuer',
        ]);

        $issued = $this->verification->ensureCode($issued);

        $collective = app(SptCollectiveDocumentService::class);
        // Render the annex BEFORE writing either archive file. Both documents
        // reuse the same issued-letter number and public verification QR.
        $annexBinary = $collective->renderAnnex(
            $letter, $issued,
            $this->verification->publicUrl($issued),
            $this->verification->qrDataUri($issued)
        );
        $snapshot = $this->snapshot($issued, $letter);

        $snapshotJson = json_encode(
            $snapshot,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
        );

        $checksum = hash('sha256', $snapshotJson);

        $rendered = $this->renderer->render(
            $letter,
            false,
            false
        );

        $binary = Pdf::loadView(
            'issued-letters.pdf',
            [
                'issued' => $issued,
                'letter' => $letter,
                'rendered' => $rendered,
                'checksum' => $checksum,
                'verificationUrl' => $this->verification->publicUrl($issued),
                'qrDataUri' => $this->verification->qrDataUri($issued),
            ]
        )
            ->setPaper('a4', 'portrait')
            ->output();

        $filename = 'surat-'.Str::slug(
            (string) $issued->number
        ).'.pdf';

        $path = 'issued-letters/'
            .$issued->letter_date->format('Y')
            .'/'
            .$issued->id
            .'/'
            .$filename;

        try {
            if (! Storage::put($path, $binary)) {
                throw new \RuntimeException('Unable to store official SPT PDF.');
            }
            $annexPath = null;
            if ($annexBinary !== null) {
                $annexPath = 'issued-letters/'.$issued->letter_date->format('Y').'/'.$issued->id.'/lampiran-spt-kolektif.pdf';
                if (! Storage::put($annexPath, $annexBinary)) {
                    throw new \RuntimeException('Unable to store official SPT annex PDF.');
                }
            }

            $issued->forceFill([
                'snapshot_json' => $snapshot,
                'checksum_sha256' => $checksum,
                'pdf_path' => $path,
                'pdf_name' => $filename,
                'annex_pdf_path' => $annexPath,
                'annex_pdf_name' => $annexPath ? 'lampiran-spt-kolektif.pdf' : null,
                'annex_file_sha256' => $annexBinary !== null ? hash('sha256', $annexBinary) : null,
                'annex_file_size' => $annexBinary !== null ? strlen($annexBinary) : null,
                'file_sha256' => hash('sha256', $binary),
                'file_size' => strlen($binary),
                'archived_document_at' => now(),
            ])->save();

            return $issued->fresh([
                'outgoingLetter',
                'letterType',
                'issuer',
            ]);
        } catch (Throwable $exception) {
            $possibleAnnex = 'issued-letters/'.$issued->letter_date->format('Y').'/'.$issued->id.'/lampiran-spt-kolektif.pdf';
            if ($annexBinary !== null && Storage::exists($possibleAnnex)) {
                Storage::delete($possibleAnnex);
            }
            if (Storage::exists($path)) {
                Storage::delete($path);
            }

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(
        IssuedLetter $issued,
        OutgoingLetter $letter
    ): array {
        return [
            'issued_letter_id' => $issued->id,
            'outgoing_letter_id' => $letter->id,
            'number' => $issued->number,
            'letter_date' => $issued->letter_date?->toDateString(),
            'subject' => $issued->subject,
            'recipient' => $issued->recipient,
            'status' => $issued->status,
            'issued_at' => $issued->issued_at?->toISOString(),
            'issued_by' => $issued->issued_by,
            'letter_type' => [
                'id' => $letter->letterType?->id,
                'code' => $letter->letterType?->code,
                'name' => $letter->letterType?->name,
            ],
            'template' => [
                'id' => $letter->template?->id,
                'name' => $letter->template?->name,
                'code' => $letter->template?->code,
                'version' => $letter->template?->version,
            ],
            'letterhead' => [
                'id' => $letter->letterheadProfile?->id,
                'name' => $letter->letterheadProfile?->name,
                'organization_name' => $letter->letterheadProfile?->organization_name,
                'parent_organization' => $letter->letterheadProfile?->parent_organization,
                'sub_parent_organization' => $letter->letterheadProfile?->sub_parent_organization,
                'address' => $letter->letterheadProfile?->address,
                'city' => $letter->letterheadProfile?->city,
            ],
            'signatory' => [
                'name' => $issued->signatory_name,
                'nip' => $issued->signatory_nip,
                'position' => $issued->signatory_position,
            ],
            // Phase 1D: immutable editable-export input, frozen during issuance.
            // Do not derive a historical DOCX from mutable current templates/personnel.
            'docx_rendered_html' => (string) $this->renderer->render($letter, false, false),
            'docx_letterhead' => [
                'parent_organization' => $letter->letterheadProfile?->parent_organization,
                'directorate_name' => $letter->letterheadProfile?->directorate_name,
                'organization_name' => $letter->letterheadProfile?->organization_name,
                'address' => $letter->letterheadProfile?->address,
                'phone' => $letter->letterheadProfile?->phone,
                'email' => $letter->letterheadProfile?->email,
            ],
            'content_html' => $letter->content_html,
            'placeholder_data' => $letter->placeholder_data,
            'notes' => $letter->notes,
            'source_spt_id' => $letter->source_spt_id,
            'collective_annex' => app(SptCollectiveDocumentService::class)->isCollective($letter),
            'personnel' => $letter->personnels()
                ->orderBy('name')
                ->get()
                ->map(fn ($person) => [
                    'id' => $person->id,
                    'name' => $person->name,
                    'nip' => $person->nip,
                    'position' => $person->position,
                    'rank' => $person->rank,
                    'grade' => $person->grade,
                ])
                ->values()
                ->all(),
        ];
    }
}
