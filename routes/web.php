<?php

use App\Livewire\AdministrationProfiles\Create as AdministrationProfileCreate;
use App\Livewire\AdministrationProfiles\Edit as AdministrationProfileEdit;
use App\Livewire\AdministrationProfiles\Index as AdministrationProfileIndex;
use App\Livewire\ActivityTypes\Create as ActivityTypeCreate;
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
use App\Http\Controllers\OutgoingLetterDocumentController;
use App\Http\Controllers\IssuedLetterVerificationController;
use App\Http\Controllers\LetterDocumentController;
use App\Http\Controllers\ReadinessController;
use App\Livewire\Security\AccountRecoveryIndex;
use App\Livewire\Users\Index as UserIndex;
use App\Livewire\Users\Create as UserCreate;
use App\Livewire\Users\Edit as UserEdit;
use App\Livewire\Roles\Index as RoleIndex;
use App\Livewire\Roles\Edit as RoleEdit;
use App\Livewire\MySpt\Index as MySptIndex;
use App\Livewire\MySpt\Show as MySptShow;
use App\Livewire\MyRecap\Index as MyRecapIndex;
use App\Livewire\IncomingLetters\Index as IncomingLetterIndex;
use App\Livewire\IncomingLetters\Create as IncomingLetterCreate;
use App\Livewire\IncomingLetters\Edit as IncomingLetterEdit;
use App\Livewire\IncomingLetters\Show as IncomingLetterShow;
use App\Livewire\OutgoingLetters\Index as OutgoingLetterIndex;
use App\Livewire\OutgoingLetters\Create as OutgoingLetterCreate;
use App\Livewire\OutgoingLetters\Edit as OutgoingLetterEdit;
use App\Livewire\OutgoingLetters\Show as OutgoingLetterShow;
use App\Livewire\IssuedLetters\Index as IssuedLetterIndex;
use App\Livewire\IssuedLetters\Show as IssuedLetterShow;
use App\Livewire\CorrespondenceRegister\Index as CorrespondenceRegisterIndex;
use Illuminate\Support\Facades\Route;

Route::get(
    '/health/ready',
    ReadinessController::class
)->middleware('throttle:30,1')
    ->name('health.ready');

Route::get(
    '/verify/surat/{code}',
    IssuedLetterVerificationController::class
)->middleware('throttle:60,1')
    ->name('issued-letters.verify');
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
    | Persuratan
    |--------------------------------------------------------------------------
    */

    Route::get('/surat-masuk', IncomingLetterIndex::class)
        ->name('incoming-letters.index');

    Route::get('/surat-masuk/create', IncomingLetterCreate::class)
        ->name('incoming-letters.create');

    Route::get('/surat-masuk/{letter}', IncomingLetterShow::class)
        ->name('incoming-letters.show');

    Route::get('/surat-masuk/{letter}/edit', IncomingLetterEdit::class)
        ->name('incoming-letters.edit');
Route::get('/surat-keluar', OutgoingLetterIndex::class)
        ->name('outgoing-letters.index');

    Route::get('/surat-keluar/create', OutgoingLetterCreate::class)
        ->name('outgoing-letters.create');

    Route::get('/surat-keluar/{letter}', OutgoingLetterShow::class)
        ->name('outgoing-letters.show');

    Route::get('/surat-keluar/{letter}/edit', OutgoingLetterEdit::class)
        ->name('outgoing-letters.edit');
    Route::get(
        '/surat-keluar/{letter}/document',
        [OutgoingLetterDocumentController::class, 'preview']
    )->name('outgoing-letters.document.preview');

    Route::get(
        '/surat-keluar/{letter}/document/print',
        [OutgoingLetterDocumentController::class, 'print']
    )->name('outgoing-letters.document.print');

    Route::get('/surat-terbit', IssuedLetterIndex::class)
        ->name('issued-letters.index');

    Route::get('/surat-terbit/{letter}', IssuedLetterShow::class)
        ->name('issued-letters.show');
    Route::get(
        '/register-persuratan',
        CorrespondenceRegisterIndex::class
    )->name('correspondence-register.index');

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
    Route::get(
        '/spt/{letter}/document',
        [LetterDocumentController::class, 'preview']
    )->name('letters.document.preview');

    Route::get(
        '/spt/{letter}/document/print',
        [LetterDocumentController::class, 'print']
    )->name('letters.document.print');

    Route::get(
        '/spt/{letter}/document/pdf',
        [LetterDocumentController::class, 'pdf']
    )->name('letters.document.pdf');

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

    /*
    |--------------------------------------------------------------------------
    | Security & Account Recovery
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/security/account-recovery',
        AccountRecoveryIndex::class
    )->middleware('password.confirm')
        ->name('security.account-recovery');

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    Route::get('/users', UserIndex::class)
        ->name('users.index');

    Route::get('/users/create', UserCreate::class)
        ->name('users.create');

    Route::get('/users/{user}/edit', UserEdit::class)
        ->name('users.edit');

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */

    Route::get('/roles', RoleIndex::class)
        ->name('roles.index');

    Route::get('/roles/{role}/edit', RoleEdit::class)
        ->name('roles.edit');

    /*
    |--------------------------------------------------------------------------
    | Personal Staff Portal
    |--------------------------------------------------------------------------
    */

    Route::get('/my/spt', MySptIndex::class)
        ->name('my-spt.index');

    Route::get('/my/spt/{letter}', MySptShow::class)
        ->name('my-spt.show');

    Route::get('/my/recap', MyRecapIndex::class)
        ->name('my-recap.index');
});

require __DIR__.'/settings.php';