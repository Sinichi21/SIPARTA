<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PhaseNineteenV2StabilizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_spt_has_one_outgoing_letter_relationship(): void
    {
        $user = User::factory()->create();

        $type = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'numbering_pattern' => '{sequence_padded}/{type}/{year}',
            'requires_personnel' => true,
            'is_active' => true,
        ]);

        $spt = Letter::create([
            'letter_type_id' => $type->id,
            'subject' => 'Penugasan Uji',
            'status' => 'published',
            'source' => 'system',
            'record_type' => 'normal',
            'personnel_scope' => 'selected',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $outgoing = OutgoingLetter::create([
            'source_spt_id' => $spt->id,
            'letter_type_id' => $type->id,
            'recipient' => 'Personil yang ditugaskan',
            'subject' => 'Penugasan Uji',
            'nature' => 'biasa',
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->assertSame(
            $outgoing->id,
            $spt->fresh()->outgoingLetter?->id
        );
    }

    public function test_opening_create_from_existing_spt_redirects_to_existing_outgoing_letter(): void
    {
        foreach ([
            'outgoing-letters.create',
            'outgoing-letters.view',
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $user = User::factory()->create();
        $user->givePermissionTo([
            'outgoing-letters.create',
            'outgoing-letters.view',
        ]);

        $type = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'numbering_pattern' => '{sequence_padded}/{type}/{year}',
            'requires_personnel' => true,
            'is_active' => true,
        ]);

        $spt = Letter::create([
            'letter_type_id' => $type->id,
            'subject' => 'Penugasan Uji',
            'status' => 'published',
            'source' => 'system',
            'record_type' => 'normal',
            'personnel_scope' => 'selected',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $outgoing = OutgoingLetter::create([
            'source_spt_id' => $spt->id,
            'letter_type_id' => $type->id,
            'recipient' => 'Personil yang ditugaskan',
            'subject' => 'Penugasan Uji',
            'nature' => 'biasa',
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('outgoing-letters.create', [
                'source_spt' => $spt->id,
            ]));

        $response->assertRedirect(
            route('outgoing-letters.show', $outgoing)
        );
    }
}
