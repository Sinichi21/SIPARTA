<?php

namespace Tests\Unit;

use App\Models\ActivityType;
use App\Services\SptActivityTypeMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SptActivityTypeMatcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            ['code' => 'MONITORING', 'name' => 'Monitoring Spektrum Frekuensi Radio'],
            ['code' => 'PENGUKURAN', 'name' => 'Pengukuran'],
            ['code' => 'PEMERIKSAAN', 'name' => 'Pemeriksaan Stasiun Radio'],
            ['code' => 'PENGAWASAN', 'name' => 'Pengawasan Perangkat'],
            ['code' => 'GANGGUAN', 'name' => 'Penanganan Gangguan'],
            ['code' => 'PENERTIBAN', 'name' => 'Penertiban'],
            ['code' => 'RAPAT', 'name' => 'Rapat / Koordinasi'],
            ['code' => 'LAINNYA', 'name' => 'Kegiatan Lainnya'],
        ] as $type) {
            ActivityType::create([
                ...$type,
                'is_active' => true,
            ]);
        }
    }

    public function test_it_maps_common_historical_activity_text(): void
    {
        $matcher = app(SptActivityTypeMatcher::class);

        $this->assertSame(
            'MONITORING',
            ActivityType::find($matcher->matchId('Monitoring frekuensi wilayah Denpasar'))->code
        );

        $this->assertSame(
            'PENGUKURAN',
            ActivityType::find($matcher->matchId('Pengukuran parameter teknis'))->code
        );

        $this->assertSame(
            'RAPAT',
            ActivityType::find($matcher->matchId('Rapat koordinasi internal'))->code
        );
    }

    public function test_it_does_not_force_unknown_activity_into_lainnya(): void
    {
        $matcher = app(SptActivityTypeMatcher::class);

        $this->assertNull(
            $matcher->matchId('Upacara 17 Agustus 2026')
        );
    }

    public function test_exact_master_name_is_matched(): void
    {
        $matcher = app(SptActivityTypeMatcher::class);

        $id = $matcher->matchId('Pemeriksaan Stasiun Radio');

        $this->assertNotNull($id);
        $this->assertSame('PEMERIKSAAN', ActivityType::find($id)->code);
    }
}
