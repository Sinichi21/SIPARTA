<?php

namespace Tests\Feature;

use App\Livewire\SptRecap\Index as SptRecap;
use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SptRecapExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private LetterType $type;
    private ActivityType $activity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Permission::findOrCreate('reports.view', 'web');
        $this->user->givePermissionTo('reports.view');
        $this->actingAs($this->user);

        $this->type = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'is_active' => true,
        ]);

        $this->activity = ActivityType::create([
            'code' => 'MONITORING',
            'name' => 'Monitoring Spektrum Frekuensi Radio',
            'is_active' => true,
        ]);
    }

    private function letter(array $attributes = []): Letter
    {
        return Letter::create(array_merge([
            'letter_type_id' => $this->type->id,
            'activity_type_id' => $this->activity->id,
            'created_by' => $this->user->id,
            'number' => 'SPT-001',
            'subject' => 'Monitoring Frekuensi',
            'letter_date' => today(),
            'start_date' => today(),
            'end_date' => today(),
            'location' => 'Denpasar',
            'status' => 'published',
            'source' => 'system',
            'record_type' => 'normal',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_SELECTED,
        ], $attributes));
    }

    public function test_export_uses_active_filters_and_exports_all_matching_rows(): void
    {
        $person = Personnel::create([
            'name' => 'Personil Uji',
            'is_active' => true,
        ]);

        $included = $this->letter([
            'number' => 'SPT-INCLUDED',
            'letter_date' => today()->setYear(2026),
        ]);
        $included->personnels()->attach($person);

        $excludedByYear = $this->letter([
            'number' => 'SPT-OLD',
            'letter_date' => today()->setYear(2025),
        ]);
        $excludedByYear->personnels()->attach($person);

        $excludedCorrection = $this->letter([
            'number' => 'SPT-CORRECTION',
            'letter_date' => today()->setYear(2026),
            'record_type' => 'attendance_correction',
        ]);
        $excludedCorrection->personnels()->attach($person);

        $component = app(SptRecap::class);
        $component->year = '2026';
        $component->recordType = 'normal';

        ob_start();
        $component->exportCsv()->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('SPT-INCLUDED', $csv);
        $this->assertStringNotContainsString('SPT-OLD', $csv);
        $this->assertStringNotContainsString('SPT-CORRECTION', $csv);
        $this->assertStringContainsString('Personil Uji', $csv);
    }

    public function test_export_represents_all_personnel_scope_without_fake_personnel(): void
    {
        $this->letter([
            'number' => 'SPT-ALL',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
        ]);

        $component = app(SptRecap::class);
        $component->year = (string) today()->year;

        ob_start();
        $component->exportCsv()->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('SPT-ALL', $csv);
        $this->assertStringContainsString('Seluruh Pegawai', $csv);
    }

    public function test_export_escapes_spreadsheet_formula_values(): void
    {
        $letter = $this->letter([
            'number' => '=1+1',
            'subject' => '@danger',
            'location' => '+62',
        ]);

        $person = Personnel::create([
            'name' => '-Formula Person',
            'is_active' => true,
        ]);
        $letter->personnels()->attach($person);

        $component = app(SptRecap::class);
        $component->year = (string) today()->year;

        ob_start();
        $component->exportCsv()->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringContainsString("'@danger", $csv);
        $this->assertStringContainsString("'+62", $csv);
        $this->assertStringContainsString("'-Formula Person", $csv);
    }
}
