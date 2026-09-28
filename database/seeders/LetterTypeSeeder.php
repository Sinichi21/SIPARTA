<?php

namespace Database\Seeders;

use App\Models\LetterType;
use Illuminate\Database\Seeder;

class LetterTypeSeeder extends Seeder
{
    public function run(): void
    {
        $letterTypes = [
            [
                'code' => 'SPT',
                'name' => 'Surat Perintah Tugas',
                'description' => 'Surat penugasan personil untuk melaksanakan kegiatan tertentu.',
                'numbering_pattern' => '{sequence}/SPT/{month_roman}/{year}',
                'requires_personnel' => true,
                'is_active' => true,
            ],
            [
                'code' => 'ND',
                'name' => 'Nota Dinas',
                'description' => 'Nota dinas internal.',
                'numbering_pattern' => null,
                'requires_personnel' => false,
                'is_active' => true,
            ],
            [
                'code' => 'SM',
                'name' => 'Surat Masuk',
                'description' => 'Surat yang diterima dari pihak internal maupun eksternal.',
                'numbering_pattern' => null,
                'requires_personnel' => false,
                'is_active' => true,
            ],
            [
                'code' => 'SK',
                'name' => 'Surat Keluar',
                'description' => 'Surat yang diterbitkan untuk pihak internal maupun eksternal.',
                'numbering_pattern' => null,
                'requires_personnel' => false,
                'is_active' => true,
            ],
        ];

        foreach ($letterTypes as $letterType) {
            LetterType::updateOrCreate(
                ['code' => $letterType['code']],
                $letterType
            );
        }
    }
}