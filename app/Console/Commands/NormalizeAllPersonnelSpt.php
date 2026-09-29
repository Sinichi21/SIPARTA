<?php

namespace App\Console\Commands;

use App\Models\Letter;
use App\Models\Personnel;
use App\Services\SptImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeAllPersonnelSpt extends Command
{
    protected $signature = 'spt:normalize-all-personnel
                            {--apply : Terapkan perubahan. Tanpa opsi ini hanya preview.}';

    protected $description =
        'Normalisasi Personnel palsu ALL/SEMUA/SELURUH PEGAWAI menjadi personnel_scope=all.';

    public function handle(): int
    {
        $aliases = Personnel::query()
            ->get()
            ->filter(
                fn (Personnel $personnel) =>
                    SptImportService::isAllPersonnelValue(
                        $personnel->name
                    )
            );

        if ($aliases->isEmpty()) {
            $this->info(
                'Tidak ditemukan Personnel alias ALL/SEMUA/SELURUH PEGAWAI.'
            );

            return self::SUCCESS;
        }

        $rows = [];
        $candidates = [];

        foreach ($aliases as $alias) {
            $letters = $alias->letters()
                ->whereHas(
                    'letterType',
                    fn ($query) =>
                        $query->where('code', 'SPT')
                )
                ->withCount('personnels')
                ->get();

            foreach ($letters as $letter) {
                $safe = $letter->personnels_count === 1;

                $rows[] = [
                    $alias->name,
                    $letter->number ?: '#'.$letter->id,
                    $letter->personnels_count,
                    $safe ? 'Aman' : 'Periksa manual',
                ];

                if ($safe) {
                    $candidates[] = [
                        'personnel_id' => $alias->id,
                        'letter_id' => $letter->id,
                    ];
                }
            }
        }

        $this->table(
            ['Alias Personnel', 'SPT', 'Jumlah relasi', 'Status'],
            $rows
        );

        $this->newLine();
        $this->info(count($candidates).' SPT aman untuk dinormalisasi.');

        if (! $this->option('apply')) {
            $this->warn(
                'Mode preview. Jalankan kembali dengan --apply untuk menerapkan.'
            );

            return self::SUCCESS;
        }

        DB::transaction(function () use ($candidates) {
            foreach ($candidates as $candidate) {
                $letter = Letter::query()
                    ->findOrFail($candidate['letter_id']);

                $letter->update([
                    'personnel_scope' =>
                        Letter::PERSONNEL_SCOPE_ALL,
                ]);

                $letter->personnels()->detach(
                    $candidate['personnel_id']
                );
            }
        });

        $this->info(count($candidates).' SPT berhasil dinormalisasi.');
        $this->comment('Master Personnel alias tidak dihapus otomatis.');

        return self::SUCCESS;
    }
}
