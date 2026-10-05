<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\LetterType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SptOutgoingSourceGuardTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        Permission::firstOrCreate([
            'name' => 'outgoing-letters.create',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('outgoing-letters.create');

        return $user;
    }

    private function spt(User $user, array $overrides = []): Letter
    {
        $type = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'is_active' => true,
        ]);

        return Letter::create(array_merge([
            'letter_type_id' => $type->id,
            'subject' => 'Penugasan Uji',
            'status' => 'published',
            'source' => 'system',
            'record_type' => 'normal',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
            'number' => '001/SPT/X/2026',
            'letter_date' => '2026-10-05',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ], $overrides));
    }

    public function test_outgoing_draft_rejects_unpublished_spt_source(): void
    {
        $user = $this->user();
        $spt = $this->spt($user, [
            'status' => 'approved',
            'number' => null,
        ]);

        $this->actingAs($user)
            ->get(route('outgoing-letters.create', ['source_spt' => $spt->id]))
            ->assertSessionHasErrors('source_spt_id');
    }

    public function test_outgoing_draft_rejects_imported_spt_source(): void
    {
        $user = $this->user();
        $spt = $this->spt($user, ['source' => 'import']);

        $this->actingAs($user)
            ->get(route('outgoing-letters.create', ['source_spt' => $spt->id]))
            ->assertSessionHasErrors('source_spt_id');
    }

    public function test_outgoing_draft_accepts_published_system_spt_with_official_identity(): void
    {
        $user = $this->user();
        $spt = $this->spt($user);

        $this->actingAs($user)
            ->get(route('outgoing-letters.create', ['source_spt' => $spt->id]))
            ->assertOk();
    }
}
