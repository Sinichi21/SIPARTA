<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SptActivityMappingCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_mapping_command_only_updates_matching_imported_spt_and_preserves_subject(): void
    {
        $user = User::factory()->create();

        $sptType = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'is_active' => true,
        ]);

        $monitoring = ActivityType::create([
            'code' => 'MONITORING',
            'name' => 'Monitoring Spektrum Frekuensi Radio',
            'is_active' => true,
        ]);

        $imported = Letter::create([
            'letter_type_id' => $sptType->id,
            'activity_type_id' => null,
            'number' => 'SPT-IMPORT-MONITORING',
            'subject' => 'Monitoring frekuensi wilayah Denpasar',
            'letter_date' => today(),
            'start_date' => today(),
            'end_date' => today(),
            'location' => 'Denpasar',
            'status' => 'published',
            'source' => 'import',
            'record_type' => 'normal',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_SELECTED,
            'created_by' => $user->id,
        ]);

        $unknown = Letter::create([
            'letter_type_id' => $sptType->id,
            'activity_type_id' => null,
            'number' => 'SPT-IMPORT-UNKNOWN',
            'subject' => 'Upacara 17 Agustus',
            'letter_date' => today(),
            'start_date' => today(),
            'end_date' => today(),
            'location' => 'Denpasar',
            'status' => 'published',
            'source' => 'import',
            'record_type' => 'normal',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_SELECTED,
            'created_by' => $user->id,
        ]);

        $system = Letter::create([
            'letter_type_id' => $sptType->id,
            'activity_type_id' => null,
            'number' => 'SPT-SYSTEM-MONITORING',
            'subject' => 'Monitoring frekuensi internal',
            'letter_date' => today(),
            'start_date' => today(),
            'end_date' => today(),
            'location' => 'Denpasar',
            'status' => 'published',
            'source' => 'system',
            'record_type' => 'normal',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_SELECTED,
            'created_by' => $user->id,
        ]);

        $this->artisan('spt:map-activity-types')->assertSuccessful();

        $this->assertNull($imported->fresh()->activity_type_id);
        $this->assertNull($unknown->fresh()->activity_type_id);
        $this->assertNull($system->fresh()->activity_type_id);

        $this->artisan('spt:map-activity-types --apply')->assertSuccessful();

        $this->assertSame($monitoring->id, $imported->fresh()->activity_type_id);
        $this->assertSame('Monitoring frekuensi wilayah Denpasar', $imported->fresh()->subject);
        $this->assertNull($unknown->fresh()->activity_type_id);
        $this->assertNull($system->fresh()->activity_type_id);
    }

    public function test_mapping_command_all_option_can_include_non_import_spt(): void
    {
        $user = User::factory()->create();

        $sptType = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'is_active' => true,
        ]);

        $rapat = ActivityType::create([
            'code' => 'RAPAT',
            'name' => 'Rapat / Koordinasi',
            'is_active' => true,
        ]);

        $letter = Letter::create([
            'letter_type_id' => $sptType->id,
            'activity_type_id' => null,
            'number' => 'SPT-SYSTEM-RAPAT',
            'subject' => 'Rapat koordinasi internal',
            'letter_date' => today(),
            'start_date' => today(),
            'end_date' => today(),
            'location' => 'Kantor',
            'status' => 'published',
            'source' => 'system',
            'record_type' => 'normal',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_SELECTED,
            'created_by' => $user->id,
        ]);

        $this->artisan('spt:map-activity-types --apply --all')->assertSuccessful();

        $this->assertSame($rapat->id, $letter->fresh()->activity_type_id);
    }
}
