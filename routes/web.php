<?php

use App\Livewire\AdministrationProfiles\Create as AdministrationProfileCreate;
use App\Livewire\AdministrationProfiles\Edit as AdministrationProfileEdit;
use App\Livewire\AdministrationProfiles\Index as AdministrationProfileIndex;use App\Livewire\ActivityTypes\Create as ActivityTypeCreate;
use App\Livewire\ActivityTypes\Edit as ActivityTypeEdit;
use App\Livewire\ActivityTypes\Index as ActivityTypeIndex;
use App\Livewire\AuditLogs\Index as AuditLogIndex;
use App\Livewire\Dashboard;
use App\Livewire\Letters\Create as LetterCreate;
use App\Livewire\Letters\Edit as LetterEdit;
use App\Livewire\Letters\Index as LetterIndex;
use App\Livewire\Letters\Show as LetterShow;
use App\Livewire\LetterTypes\Create as LetterTypeCreate;
use App\Livewire\LetterTypes\Edit as LetterTypeEdit;
use App\Livewire\LetterTypes\Index as LetterTypeIndex;
use App\Livewire\LetterTemplates\Create as LetterTemplateCreate;
use App\Livewire\LetterTemplates\Edit as LetterTemplateEdit;
use App\Livewire\LetterTemplates\Index as LetterTemplateIndex;
use App\Livewire\Personnel\Create as PersonnelCreate;
use App\Livewire\Personnel\Edit as PersonnelEdit;
use App\Livewire\Personnel\Index as PersonnelIndex;
use App\Livewire\PersonnelDuplicates\Index as PersonnelDuplicatesIndex;
use App\Livewire\SptRecap\Index as SptRecapIndex;
use App\Livewire\PersonnelRecap\Index as PersonnelRecapIndex;
use App\Livewire\SptImport\Index as SptImportIndex;
use App\Livewire\Units\Create as UnitCreate;
use App\Livewire\Units\Edit as UnitEdit;
use App\Livewire\Units\Index as UnitIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', Dashboard::class)
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Personnel
    |--------------------------------------------------------------------------
    */

    Route::get('/personnel', PersonnelIndex::class)
        ->name('personnels.index');

    Route::get('/personnel/create', PersonnelCreate::class)
        ->name('personnels.create');

    Route::get('/personnel/{personnel}/edit', PersonnelEdit::class)
        ->name('personnels.edit');

    Route::get('/personnel-duplicates', PersonnelDuplicatesIndex::class)
        ->name('personnel-duplicates.index');

    /*
    |--------------------------------------------------------------------------
    | Units
    |--------------------------------------------------------------------------
    */

    Route::get('/units', UnitIndex::class)
        ->name('units.index');

    Route::get('/units/create', UnitCreate::class)
        ->name('units.create');

    Route::get('/units/{unit}/edit', UnitEdit::class)
        ->name('units.edit');

    /*
    |--------------------------------------------------------------------------
    | Activity Types
    |--------------------------------------------------------------------------
    */

    Route::get('/activity-types', ActivityTypeIndex::class)
        ->name('activity-types.index');

    Route::get('/activity-types/create', ActivityTypeCreate::class)
        ->name('activity-types.create');

    Route::get(
        '/activity-types/{activityType}/edit',
        ActivityTypeEdit::class
    )->name('activity-types.edit');

    /*
    |--------------------------------------------------------------------------
    | Letter Types
    |--------------------------------------------------------------------------
    */

    Route::get('/letter-types', LetterTypeIndex::class)
        ->name('letter-types.index');

    Route::get('/letter-types/create', LetterTypeCreate::class)
        ->name('letter-types.create');

    Route::get(
        '/letter-types/{letterType}/edit',
        LetterTypeEdit::class
    )->name('letter-types.edit');

    /*
    |--------------------------------------------------------------------------
    | SPT
    |--------------------------------------------------------------------------
    */

    Route::get('/spt', LetterIndex::class)
        ->name('letters.index');

    Route::get('/spt/create', LetterCreate::class)
        ->name('letters.create');

    Route::get('/spt/{letter}', LetterShow::class)
        ->name('letters.show');

    Route::get('/spt/{letter}/edit', LetterEdit::class)
        ->name('letters.edit');

    Route::get('/spt-recap', SptRecapIndex::class)
        ->name('spt-recap.index');

    Route::get('/personnel-recap', PersonnelRecapIndex::class)
        ->name('personnel-recap.index');

    Route::get('/spt-import', SptImportIndex::class)
        ->name('spt-import.index');

    /*
    |--------------------------------------------------------------------------
    | Letter Templates
    |--------------------------------------------------------------------------
    */

    Route::get('/letter-templates', LetterTemplateIndex::class)
        ->name('letter-templates.index');

    Route::get('/letter-templates/create', LetterTemplateCreate::class)
        ->name('letter-templates.create');

    Route::get(
        '/letter-templates/{letterTemplate}/edit',
        LetterTemplateEdit::class
    )->name('letter-templates.edit');


    /*
    |--------------------------------------------------------------------------
    | Kop & Administrasi Surat
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/administration-profiles',
        AdministrationProfileIndex::class
    )->name('administration-profiles.index');

    Route::get(
        '/administration-profiles/create',
        AdministrationProfileCreate::class
    )->name('administration-profiles.create');

    Route::get(
        '/administration-profiles/{letterheadProfile}/edit',
        AdministrationProfileEdit::class
    )->name('administration-profiles.edit');
    /*
    |--------------------------------------------------------------------------
    | Audit Log
    |--------------------------------------------------------------------------
    */

    Route::get('/audit-logs', AuditLogIndex::class)
        ->name('audit-logs.index');
});

require __DIR__.'/settings.php';