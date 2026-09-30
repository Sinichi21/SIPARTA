<?php

namespace App\Services;

use App\Enums\LetterStatus;
use App\Models\Letter;
use App\Models\LetterDocumentSnapshot;
use App\Models\LetterTemplate;
use App\Models\LetterheadProfile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LetterDocumentService
{
    public function __construct(
        private readonly LetterTemplateRenderer $renderer,
        private readonly AuditService $audit,
    ) {}

    public function snapshotPublished(Letter $letter, ?int $userId): ?LetterDocumentSnapshot
    {
        if ($letter->status !== LetterStatus::Published) {
            return null;
        }

        if ($existing = $letter->documentSnapshot()->first()) {
            return $existing;
        }

        $template = $this->defaultTemplateFor($letter);

        // Publishing remains usable even before document templates are configured.
        if (! $template) {
            return null;
        }

        return $this->createSnapshot($letter, $template, $userId, false);
    }

    public function payloadFor(Letter $letter, ?int $userId = null): array
    {
        $letter->loadMissing(['letterType', 'activityType', 'personnels.unit']);

        if ($snapshot = $letter->documentSnapshot()->first()) {
            return $this->payloadFromSnapshot($snapshot);
        }

        $template = $this->defaultTemplateFor($letter);

        if (! $template) {
            throw ValidationException::withMessages([
                'document' => 'Belum ada template default aktif untuk jenis surat ini.',
            ]);
        }

        if ($letter->status === LetterStatus::Published) {
            return $this->payloadFromSnapshot(
                $this->createSnapshot($letter, $template, $userId, true)
            );
        }

        return $this->livePayload($letter, $template);
    }

    private function defaultTemplateFor(Letter $letter): ?LetterTemplate
    {
        return LetterTemplate::query()
            ->where('letter_type_id', $letter->letter_type_id)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }

    private function createSnapshot(
        Letter $letter,
        LetterTemplate $template,
        ?int $userId,
        bool $legacyGenerated
    ): LetterDocumentSnapshot {
        $letter->loadMissing(['letterType', 'activityType', 'personnels.unit']);
        $template->loadMissing('letterheadProfile');

        $letterhead = $this->renderer->resolveLetterhead($template);
        $renderedHtml = (string) $this->renderer->render($template, $letter);
        $letterSnapshot = $this->letterSnapshot($letter, $legacyGenerated);
        $letterheadSnapshot = $this->letterheadSnapshot($letterhead);

        $checksum = hash('sha256', json_encode([
            'rendered_html' => $renderedHtml,
            'letter' => $letterSnapshot,
            'letterhead' => $letterheadSnapshot,
            'template' => [
                'id' => $template->id,
                'code' => $template->code,
                'version' => $template->version,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        $snapshot = LetterDocumentSnapshot::create([
            'letter_id' => $letter->id,
            'letter_template_id' => $template->id,
            'template_name' => $template->name,
            'template_code' => $template->code,
            'template_version' => $template->version,
            'rendered_html' => $renderedHtml,
            'letter_snapshot' => $letterSnapshot,
            'letterhead_snapshot' => $letterheadSnapshot,
            'checksum_sha256' => $checksum,
            'generated_by' => $userId,
            'generated_at' => now(),
        ]);

        $this->audit->documentGenerated($snapshot);

        return $snapshot;
    }

    private function livePayload(Letter $letter, LetterTemplate $template): array
    {
        $letterhead = $this->renderer->resolveLetterhead($template);

        return [
            'is_snapshot' => false,
            'legacy_generated' => false,
            'rendered_html' => (string) $this->renderer->render($template, $letter),
            'letter' => $this->letterSnapshot($letter, false),
            'letterhead' => $this->letterheadSnapshot($letterhead),
            'template' => [
                'name' => $template->name,
                'code' => $template->code,
                'version' => $template->version,
            ],
            'checksum_sha256' => null,
            'generated_at' => null,
        ];
    }

    private function payloadFromSnapshot(LetterDocumentSnapshot $snapshot): array
    {
        return [
            'is_snapshot' => true,
            'legacy_generated' => (bool) ($snapshot->letter_snapshot['legacy_generated'] ?? false),
            'rendered_html' => $snapshot->rendered_html,
            'letter' => $snapshot->letter_snapshot,
            'letterhead' => $snapshot->letterhead_snapshot,
            'template' => [
                'name' => $snapshot->template_name,
                'code' => $snapshot->template_code,
                'version' => $snapshot->template_version,
            ],
            'checksum_sha256' => $snapshot->checksum_sha256,
            'generated_at' => $snapshot->generated_at,
        ];
    }

    private function letterSnapshot(Letter $letter, bool $legacyGenerated): array
    {
        return [
            'id' => $letter->id,
            'number' => $letter->number,
            'subject' => $letter->subject,
            'letter_date' => $letter->letter_date?->toDateString(),
            'start_date' => $letter->start_date?->toDateString(),
            'end_date' => $letter->end_date?->toDateString(),
            'location' => $letter->location,
            'status' => $letter->status->value,
            'personnel_scope' => $letter->personnel_scope,
            'personnel_names' => $letter->assignsAllPersonnel()
                ? ['Seluruh Pegawai']
                : $letter->personnels->pluck('name')->values()->all(),
            'legacy_generated' => $legacyGenerated,
        ];
    }

    private function letterheadSnapshot(?LetterheadProfile $profile): ?array
    {
        if (! $profile) {
            return null;
        }

        $logoDataUri = null;
        $logoSecondaryDataUri = null;

        if ($profile->logo_path && Storage::disk('public')->exists($profile->logo_path)) {
            $contents = Storage::disk('public')->get($profile->logo_path);
            $mime = Storage::disk('public')->mimeType($profile->logo_path) ?: 'image/png';
            $logoDataUri = 'data:'.$mime.';base64,'.base64_encode($contents);
        }

        if (
            $profile->logo_secondary_path
            && Storage::disk('public')->exists($profile->logo_secondary_path)
        ) {
            $contents = Storage::disk('public')->get($profile->logo_secondary_path);
            $mime = Storage::disk('public')->mimeType($profile->logo_secondary_path) ?: 'image/png';
            $logoSecondaryDataUri = 'data:'.$mime.';base64,'.base64_encode($contents);
        }

        return [
            'profile_id' => $profile->id,
            'profile_name' => $profile->name,
            'organization_name' => $profile->organization_name,
            'parent_organization' => $profile->parent_organization,
            'address' => $profile->address,
            'phone' => $profile->phone,
            'email' => $profile->email,
            'website' => $profile->website,
            'city' => $profile->city,
            'signatory_name' => $profile->signatory_name,
            'signatory_nip' => $profile->signatory_nip,
            'signatory_position' => $profile->signatory_position,
            'logo_data_uri' => $logoDataUri,
            'logo_secondary_data_uri' => $logoSecondaryDataUri,
        ];
    }
}
