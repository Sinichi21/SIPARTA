<?php

namespace Tests\Feature;

use App\Livewire\CorrespondenceRegister\Index;
use App\Models\IncomingLetter;
use App\Models\IssuedLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PhaseSeventeenCorrespondenceRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_can_filter_issued_letters_by_status(): void
    {
        Permission::firstOrCreate([
            'name' => 'reports.view',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');

        $this->actingAs($user);

        $activeOutgoing = OutgoingLetter::create([
            'recipient' => 'A',
            'subject' => 'Aktif',
            'nature' => 'biasa',
            'status' => 'published',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $revokedOutgoing = OutgoingLetter::create([
            'recipient' => 'B',
            'subject' => 'Dicabut',
            'nature' => 'biasa',
            'status' => 'published',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        IssuedLetter::create([
            'outgoing_letter_id' => $activeOutgoing->id,
            'number' => '001/TEST/2026',
            'letter_date' => '2026-09-30',
            'subject' => 'Aktif',
            'recipient' => 'A',
            'issued_at' => now(),
            'status' => 'active',
        ]);

        IssuedLetter::create([
            'outgoing_letter_id' => $revokedOutgoing->id,
            'number' => '002/TEST/2026',
            'letter_date' => '2026-09-30',
            'subject' => 'Dicabut',
            'recipient' => 'B',
            'issued_at' => now(),
            'status' => 'revoked',
        ]);

        Livewire::test(Index::class)
            ->set('documentType', 'issued')
            ->set('year', '2026')
            ->set('status', 'revoked')
            ->assertSee('002/TEST/2026')
            ->assertDontSee('001/TEST/2026');
    }

    public function test_register_switches_between_document_types(): void
    {
        Permission::firstOrCreate([
            'name' => 'reports.view',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');

        $this->actingAs($user);

        IncomingLetter::create([
            'agenda_number' => 'AG-001',
            'number' => 'IN-001',
            'received_date' => '2026-09-30',
            'sender' => 'Instansi A',
            'subject' => 'Surat Masuk',
            'status' => 'recorded',
        ]);

        OutgoingLetter::create([
            'recipient' => 'Instansi B',
            'subject' => 'Surat Keluar',
            'nature' => 'biasa',
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        Livewire::test(Index::class)
            ->set('documentType', 'incoming')
            ->set('year', '2026')
            ->assertSee('AG-001')
            ->set('documentType', 'outgoing')
            ->set('year', '2026')
            ->assertSee('Surat Keluar');
    }
}
