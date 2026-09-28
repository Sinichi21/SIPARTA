<?php

namespace Database\Seeders;

use App\Models\ActivityType;
use Illuminate\Database\Seeder;

class ActivityTypeSeeder extends Seeder
{
    public function run(): void
    {
        $activityTypes = [
            [
                'code' => 'MONITORING',
                'name' => 'Monitoring Spektrum Frekuensi Radio',
            ],
            [
                'code' => 'PENGUKURAN',
                'name' => 'Pengukuran',
            ],
            [
                'code' => 'PEMERIKSAAN',
                'name' => 'Pemeriksaan Stasiun Radio',
            ],
            [
                'code' => 'PENGAWASAN',
                'name' => 'Pengawasan Perangkat',
            ],
            [
                'code' => 'GANGGUAN',
                'name' => 'Penanganan Gangguan',
            ],
            [
                'code' => 'PENERTIBAN',
                'name' => 'Penertiban',
            ],
            [
                'code' => 'RAPAT',
                'name' => 'Rapat / Koordinasi',
            ],
            [
                'code' => 'LAINNYA',
                'name' => 'Kegiatan Lainnya',
            ],
        ];

        foreach ($activityTypes as $activityType) {
            ActivityType::updateOrCreate(
                ['code' => $activityType['code']],
                [
                    ...$activityType,
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}