<?php

namespace App\Console\Commands;

use App\Models\IssuedLetter;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\OutgoingLetter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/** Read-only readiness audit. Does not mutate sequence numbers or historical documents. */
class AuditSptDocuments extends Command
{
    protected $signature = 'siparta:audit-spt-documents {--strict : Return failure when actionable discrepancies are found}';
    protected $description = 'Read-only audit for linked SPT numbers, official PDFs, collective annexes and template defaults';

    public function handle(): int
    {
        foreach (['letters', 'outgoing_letters', 'issued_letters', 'letter_templates', 'letter_types'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Missing table: {$table}. Run migrations first.");
                return self::FAILURE;
            }
        }

        $issues = 0;
        $this->info('SIPARTA SPT document readiness — read-only');

        $defaults = LetterTemplate::query()->whereHas('letterType', fn ($q) => $q->where('code', 'SPT'))
            ->where('is_default', true)->where('is_active', true)->count();
        $this->line("Active default SPT templates: {$defaults}");
        if ($defaults !== 1) {
            $this->warn('Expected exactly one active default SPT template.');
            ++$issues;
        }
        $concept = LetterTemplate::query()->where('code', 'SPT_SYSTEM_COLLECTIVE_V1')
            ->where('is_active', true)->count();
        if ($concept) {
            $this->warn('Phase 1A collective concept is still active. It cannot be published; consider disabling after reviewing drafts.');
            ++$issues;
        }

        $linkedCount = 0;
        $mismatchCount = 0;
        OutgoingLetter::query()->whereNotNull('source_spt_id')->with('sourceSpt')
            ->orderBy('id')->chunkById(100, function ($outgoings) use (&$linkedCount, &$mismatchCount): void {
                foreach ($outgoings as $outgoing) {
                    ++$linkedCount;
                    $source = $outgoing->sourceSpt;
                    if (! $source) {
                        ++$mismatchCount;
                        continue;
                    }
                    // Drafts legitimately have no official outgoing number yet.
                    if (! filled($outgoing->number)) {
                        continue;
                    }
                    if (trim((string) $outgoing->number) !== trim((string) $source->number)
                        || $outgoing->letter_date?->toDateString() !== $source->letter_date?->toDateString()) {
                        ++$mismatchCount;
                    }
                }
            });
        $this->line("Linked outgoing SPT: {$linkedCount}; issued number/date mismatches: {$mismatchCount}");
        $issues += $mismatchCount;

        $pdfMissing = 0;
        $pdfCorrupt = 0;
        $annexMissing = 0;
        $annexCorrupt = 0;
        $collectiveMissingAnnex = 0;
        $docxSnapshotMissing = 0;
        $archiveDisk = Storage::disk(config('filesystems.default'));
        $hasAnnexColumns = Schema::hasColumn('issued_letters', 'annex_pdf_path')
            && Schema::hasColumn('issued_letters', 'annex_file_sha256');
        IssuedLetter::query()->with(['outgoingLetter.template', 'outgoingLetter.letterType'])->orderBy('id')
            ->chunkById(50, function ($items) use ($archiveDisk, $hasAnnexColumns, &$pdfMissing, &$pdfCorrupt, &$annexMissing, &$annexCorrupt, &$collectiveMissingAnnex, &$docxSnapshotMissing): void {
                foreach ($items as $issued) {
                    if (! $issued->pdf_path || ! $archiveDisk->exists($issued->pdf_path)) {
                        ++$pdfMissing;
                    } elseif ($issued->file_sha256 && ! hash_equals((string) $issued->file_sha256, hash('sha256', $archiveDisk->get($issued->pdf_path)))) {
                        ++$pdfCorrupt;
                    }
                    $isCollective = $issued->outgoingLetter?->template?->code === 'SPT_SYSTEM_COLLECTIVE_V2';
                    if ($isCollective && (! $hasAnnexColumns || ! $issued->annex_pdf_path)) {
                        ++$collectiveMissingAnnex;
                    }
                    if ($hasAnnexColumns && $issued->annex_pdf_path) {
                        if (! $archiveDisk->exists($issued->annex_pdf_path)) {
                            ++$annexMissing;
                        } elseif (! $issued->annex_file_sha256 || ! hash_equals((string) $issued->annex_file_sha256, hash('sha256', $archiveDisk->get($issued->annex_pdf_path)))) {
                            ++$annexCorrupt;
                        }
                    }
                    // Informational only: older issued documents cannot safely produce historical DOCX.
                    if ($issued->outgoingLetter?->letterType?->code === 'SPT'
                        && blank(data_get($issued->snapshot_json, 'docx_rendered_html'))) {
                        ++$docxSnapshotMissing;
                    }
                }
            });
        $this->line("Missing main PDF: {$pdfMissing}; mismatched main PDF checksum: {$pdfCorrupt}");
        $this->line("Collective without annex: {$collectiveMissingAnnex}; missing annex file: {$annexMissing}; invalid annex checksum: {$annexCorrupt}");
        $this->line("Legacy documents without DOCX snapshot (informational): {$docxSnapshotMissing}");
        $issues += $pdfMissing + $pdfCorrupt + $collectiveMissingAnnex + $annexMissing + $annexCorrupt;

        $this->line("Actionable discrepancies: {$issues}");
        $this->comment('No database rows, numbering sequences, PDF files or template settings were changed.');
        return $issues > 0 && $this->option('strict') ? self::FAILURE : self::SUCCESS;
    }
}
