<?php

namespace App\Console\Commands;

use App\Models\Letter;
use App\Services\SptActivityTypeMatcher;
use Illuminate\Console\Command;

class MapHistoricalSptActivityTypes extends Command
{
    protected $signature = 'spt:map-activity-types
        {--apply : Terapkan hasil mapping ke database}
        {--all : Sertakan SPT non-import yang belum memiliki jenis kegiatan}';

    protected $description = 'Preview atau terapkan mapping jenis kegiatan untuk SPT yang belum dikategorikan.';

    public function handle(SptActivityTypeMatcher $matcher): int
    {
        $query = Letter::query()
            ->spt()
            ->whereNull('activity_type_id');

        if (! $this->option('all')) {
            $query->where('source', 'import');
        }

        $letters = $query
            ->orderBy('letter_date')
            ->orderBy('id')
            ->get();

        $rows = [];
        $matched = 0;
        $unmatched = 0;

        foreach ($letters as $letter) {
            $sourceText = $letter->subject;
            $activityTypeId = $matcher->matchId($sourceText);

            if ($activityTypeId) {
                $matched++;

                if ($this->option('apply')) {
                    $letter->update([
                        'activity_type_id' => $activityTypeId,
                    ]);
                }
            } else {
                $unmatched++;
            }

            $rows[] = [
                $letter->number ?: 'Draft #'.$letter->id,
                optional($letter->letter_date)->format('Y-m-d') ?: '-',
                mb_strimwidth((string) $sourceText, 0, 60, '…'),
                $activityTypeId
                    ? optional(\App\Models\ActivityType::find($activityTypeId))->name
                    : 'Belum dipetakan',
                $activityTypeId ? 'Cocok' : 'Perlu review',
            ];
        }

        $this->table(
            ['Nomor SPT', 'Tanggal', 'Kegiatan/Perihal', 'Hasil Mapping', 'Status'],
            $rows
        );

        $this->newLine();
        $this->info("Cocok otomatis: {$matched}");
        $this->warn("Perlu review manual: {$unmatched}");

        if (! $this->option('apply')) {
            $this->comment('Mode preview. Jalankan kembali dengan --apply untuk menerapkan hasil yang cocok.');
        } else {
            $this->info("{$matched} SPT berhasil diperbarui.");
            $this->comment('SPT yang tidak cocok dibiarkan tanpa kategori agar tidak terjadi salah klasifikasi.');
        }

        return self::SUCCESS;
    }
}
