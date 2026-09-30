<?php

namespace Tests\Feature;

use App\Models\LetterTemplate;
use App\Models\LetterType;
use App\Models\LetterheadProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterheadAdministrationPhaseEightTest extends TestCase
{
    use RefreshDatabase;

    public function test_letter_template_can_reference_letterhead_profile(): void
    {
        $user = User::factory()->create();

        $type = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'is_active' => true,
        ]);

        $profile = LetterheadProfile::create([
            'name' => 'Kop Utama',
            'organization_name' => 'Balai Monitor',
            'city' => 'Denpasar',
            'signatory_name' => 'Pejabat Contoh',
            'signatory_nip' => '123456',
            'signatory_position' => 'Kepala Balai',
            'is_default' => true,
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $template = LetterTemplate::create([
            'letter_type_id' => $type->id,
            'letterhead_profile_id' => $profile->id,
            'name' => 'Template SPT',
            'code' => 'SPT_DEFAULT',
            'content_html' => '<p>{{instansi}}</p>',
            'version' => 1,
            'is_default' => true,
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->assertTrue(
            $template->letterheadProfile->is($profile)
        );
    }

    public function test_default_letterhead_profile_is_active(): void
    {
        $user = User::factory()->create();

        $profile = LetterheadProfile::create([
            'name' => 'Kop Default',
            'organization_name' => 'Instansi',
            'is_default' => true,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $this->assertTrue($profile->is_default);
        $this->assertTrue($profile->is_active);
    }
}
