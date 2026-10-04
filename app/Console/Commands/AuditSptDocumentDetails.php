<?php

namespace App\Console\Commands;

use App\Models\IssuedLetter;
use App\Models\Letter;
use App\Models\OutgoingLetter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/** Per-document, read-only diagnostics. No repairs, no sequence allocation. */
class AuditSptDocumentDetails extends Command
{
    protected $signature = 'siparta:audit-spt-detail {--json : Output machine-readable JSON instead of a table} {--limit=200 : Maximum details per issue category}';
    protected $description = 'List specific SPT/outgoing/issued records requiring review before any numbering migration';

    public function handle(): int
    {
        foreach (['letters', 'letter_types', 'outgoing_letters', 'issued_letters'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Missing table: {$table}. Run migrations first.");
                return self::FAILURE;
            }
        }

        $limit = (int) $this->option('limit');
        if ($limit < 1 || $limit > 10000) {
            $this->error('--limit must be from 1 to 10000.');
            return self::FAILURE;
        }

        $details = [
            'linked_mismatches' => [],
            'orphaned_links' => [],
            'missing_main_pdfs' => [],
            'invalid_main_checksums' => [],
            'missing_collective_annexes' => [],
            'invalid_annex_checksums' => [],
            'duplicate_official_numbers' => [],
        ];
        $counts = array_fill_keys(array_keys($details), 0);
        $add = static function (string $group, array $item) use (&$details, &$counts, $limit): void {
            ++$counts[$group];
            if (count($details[$group]) < $limit) {
                $details[$group][] = $item;
            }
        };

        OutgoingLetter::query()->whereNotNull('source_spt_id')->with('sourceSpt')
            ->chunkById(100, function ($outgoings) use ($add): void {
                foreach ($outgoings as $outgoing) {
                    $source = $outgoing->sourceSpt;
                    if (! $source) {
                        $add('orphaned_links', ['outgoing_id' => $outgoing->id, 'source_spt_id' => $outgoing->source_spt_id]);
                        continue;
                    }
                    // An outgoing draft legitimately has no official number.
                    if (blank($outgoing->number)) {
                        continue;
                    }
                    if (trim((string) $outgoing->number) !== trim((string) $source->number)
                        || $outgoing->letter_date?->toDateString() !== $source->letter_date?->toDateString()) {
                        $add('linked_mismatches', [
                            'source_spt_id' => $source->id,
                            'outgoing_id' => $outgoing->id,
                            'spt_number' => $source->number,
                            'outgoing_number' => $outgoing->number,
                            'spt_date' => $source->letter_date?->toDateString(),
                            'outgoing_date' => $outgoing->letter_date?->toDateString(),
                        ]);
                    }
                }
            });

        $disk = Storage::disk(config('filesystems.default'));
        $hasAnnex = Schema::hasColumn('issued_letters', 'annex_pdf_path')
            && Schema::hasColumn('issued_letters', 'annex_file_sha256');
        IssuedLetter::query()->with('outgoingLetter.template')->chunkById(50, function ($issuedLetters) use ($disk, $hasAnnex, $add): void {
            foreach ($issuedLetters as $issued) {
                $identity = ['issued_id' => $issued->id, 'outgoing_id' => $issued->outgoing_letter_id, 'number' => $issued->number];
                if (blank($issued->pdf_path) || ! $disk->exists($issued->pdf_path)) {
                    $add('missing_main_pdfs', $identity);
                } elseif (filled($issued->file_sha256)
                    && ! hash_equals((string) $issued->file_sha256, hash('sha256', $disk->get($issued->pdf_path)))) {
                    $add('invalid_main_checksums', $identity);
                }
                $collective = $issued->outgoingLetter?->template?->code === 'SPT_SYSTEM_COLLECTIVE_V2';
                if ($collective && (! $hasAnnex || blank($issued->annex_pdf_path))) {
                    $add('missing_collective_annexes', $identity);
                } elseif ($hasAnnex && filled($issued->annex_pdf_path)) {
                    if (! $disk->exists($issued->annex_pdf_path)) {
                        $add('missing_collective_annexes', $identity);
                    } elseif (blank($issued->annex_file_sha256)
                        || ! hash_equals((string) $issued->annex_file_sha256, hash('sha256', $disk->get($issued->annex_pdf_path)))) {
                        $add('invalid_annex_checksums', $identity);
                    }
                }
            }
        });

        // Detect genuine duplicates within each official register, not the intended
        // reuse of one number across a linked SPT and its outgoing counterpart.
        foreach ([
            ['model' => Letter::class, 'register' => 'spt'],
            ['model' => IssuedLetter::class, 'register' => 'issued'],
        ] as $register) {
            $register['model']::query()->whereNotNull('number')->where('number', '<>', '')
                ->selectRaw('number, COUNT(*) AS matches')
                ->groupBy('number')->havingRaw('COUNT(*) > 1')->orderBy('number')
                ->get()->each(function ($duplicate) use ($add, $register): void {
                    $add('duplicate_official_numbers', [
                        'register' => $register['register'],
                        'number' => $duplicate->number,
                        'count' => (int) $duplicate->matches,
                    ]);
                });
        }

        if ($this->option('json')) {
            $this->line(json_encode(['counts' => $counts, 'details' => $details, 'truncated_at' => $limit],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $rows = [];
            foreach ($counts as $group => $count) {
                $rows[] = [$group, $count, count($details[$group])];
            }
            $this->table(['Category', 'Total', 'Details displayed'], $rows);
            foreach ($details as $group => $items) {
                foreach ($items as $item) {
                    $this->line($group.': '.json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }
            $this->comment('Read-only. No data, numbers or stored documents have been changed.');
        }
        return self::SUCCESS;
    }
}
