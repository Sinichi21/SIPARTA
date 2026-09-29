<?php

use App\Services\SptImportService;

it('recognizes all personnel aliases during SPT import', function (string $value) {
    expect(SptImportService::isAllPersonnelValue($value))->toBeTrue();
})->with([
    'ALL PEGAWAI',
    'Semua Pegawai',
    'seluruh pegawai',
    'ALL PERSONIL',
    'Semua Personil',
    'SELURUH PERSONIL',
]);

it('does not classify an ordinary personnel name as all personnel', function () {
    expect(
        SptImportService::isAllPersonnelValue(
            'I Wayan Contoh, S.Kom.'
        )
    )->toBeFalse();
});
