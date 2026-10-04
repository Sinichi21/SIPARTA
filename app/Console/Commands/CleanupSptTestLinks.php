<?php

namespace App\Console\Commands;

use App\Models\OutgoingLetter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Explicit, narrow development-data cleanup. Never touches issued archives. */
class CleanupSptTestLinks extends Command
{
    protected $signature = 'siparta:cleanup-test-spt-links
        {--apply : Actually remove the two audited test links when safe}
        {--ack= : Must equal DELETE-DEV-TEST-LINKS when applying}';

    protected $description = 'Preview or remove two specific unissued test outgoing-SPT records in a local development DB.';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Refused: APP_ENV must be local (development workstation only).');
            return self::FAILURE;
        }

        if (! config('database.connections.'.config('database.default').'.database')) {
            $this->error('Refused: database connection has no explicit database name.');
            return self::FAILURE;
        }

        // This allow-list matches the IDs AND observed number pairs from the Oct 4 diagnostic.
        $targets = [
            5 => ['source' => 504, 'source_number' => '001/SPT/IX/2026', 'outgoing_number' => '5/SPT/IX/2026'],
            6 => ['source' => 499, 'source_number' => '162/Balmon.51/KP.01.06/09/2026', 'outgoing_number' => '6/SPT/X/2026'],
        ];

        $eligible = [];
        $blocked = false;
        foreach ($targets as $id => $expected) {
            $outgoing = OutgoingLetter::with(['sourceSpt', 'issuedLetter'])->find($id);
            if (! $outgoing) {
                $this->line("Outgoing #{$id}: not found; no action.");
                continue;
            }
            $identityMatches = $outgoing->source_spt_id === $expected['source']
                && trim((string) $outgoing->number) === $expected['outgoing_number']
                && trim((string) $outgoing->sourceSpt?->number) === $expected['source_number'];
            if (! $identityMatches || $outgoing->issuedLetter !== null
                || in_array($outgoing->status?->value, ['published', 'sent', 'archived'], true)) {
                $this->warn("Outgoing #{$id}: BLOCKED — changed identity or an official issued letter exists. No deletion.");
                $blocked = true;
                continue;
            }
            $eligible[] = $id;
            $this->line("Outgoing #{$id}: eligible ONLY if this is intentionally disposable testing data (linked SPT is kept).");
        }

        $this->warn('Issued letter #1 and all published PDF/annex files are NEVER deleted by this command.');
        if (! $this->option('apply')) {
            $this->info('DRY RUN; zero changes. For reviewed disposable unissued rows only: --apply --ack=DELETE-DEV-TEST-LINKS');
            return self::SUCCESS;
        }
        if ($blocked || $this->option('ack') !== 'DELETE-DEV-TEST-LINKS') {
            $this->error('Refused: blocked record(s) or missing explicit acknowledgement.');
            return self::FAILURE;
        }
        DB::transaction(function () use ($eligible, $targets): void {
            foreach ($eligible as $id) {
                $row = OutgoingLetter::query()->lockForUpdate()->findOrFail($id);
                $exp = $targets[$id];
                // Guard again under lock; snapshot rows may be created between preview and apply.
                if ($row->issuedLetter()->exists()
                    || in_array($row->status?->value, ['published', 'sent', 'archived'], true)
                    || $row->source_spt_id !== $exp['source']
                    || trim((string) $row->number) !== $exp['outgoing_number']
                    || trim((string) $row->sourceSpt?->number) !== $exp['source_number']) {
                    throw new \RuntimeException("Outgoing #{$id} changed; cleanup rolled back.");
                }
                $row->personnels()->detach();
                $row->forceDelete();
                $this->info("Removed unissued test outgoing #{$id}. SPT source was kept.");
            }
        });
        return self::SUCCESS;
    }
}
