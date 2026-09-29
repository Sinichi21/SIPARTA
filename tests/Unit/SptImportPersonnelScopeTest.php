<?php

namespace Tests\Unit;

use App\Services\SptImportService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SptImportPersonnelScopeTest extends TestCase
{
    public static function allPersonnelAliases(): array
    {
        return [
            ['ALL PEGAWAI'],
            ['Semua Pegawai'],
            ['seluruh pegawai'],
            ['ALL PERSONIL'],
            ['Semua Personil'],
            ['SELURUH PERSONIL'],
        ];
    }

    #[DataProvider('allPersonnelAliases')]
    public function test_recognizes_all_personnel_aliases_during_spt_import(
        string $value
    ): void {
        $this->assertTrue(
            SptImportService::isAllPersonnelValue($value)
        );
    }

    public function test_does_not_classify_an_ordinary_personnel_name_as_all_personnel(): void
    {
        $this->assertFalse(
            SptImportService::isAllPersonnelValue(
                'I Wayan Contoh, S.Kom.'
            )
        );
    }
}
