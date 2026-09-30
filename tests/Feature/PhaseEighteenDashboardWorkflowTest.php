<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\IncomingLetter;
use App\Models\IssuedLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PhaseEighteenDashboardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_correspondence_workflow_summary(): void
    {
        foreach ([
            'dashboard.view',
            'incoming-letters.view',
            'outgoing-letters.view',
            'issued-letters.view',
            'letters.view',
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $user = User::factory()->create();
        $user->givePermissionTo([
            'dashboard.view',
            'incoming-letters.view',
            'outgoing-letters.view',
            'issued-letters.view',
            'letters.view',
        ]);

        $this->actingAs($user);

        IncomingLetter::create([
            'agenda_number' => 'AG-018',
            'received_date' => today(),
            'sender' => 'Instansi A',
            'subject' => 'Perlu Diproses',
            'status' => 'processing',
        ]);

        $outgoing = OutgoingLetter::create([
            'recipient' => 'Instansi B',
            'subject' => 'Menunggu Persetujuan',
            'nature' => 'biasa',
            'status' => 'approved',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        IssuedLetter::create([
            'outgoing_letter_id' => $outgoing->id,
            'number' => '018/TEST/IX/2026',
            'letter_date' => today(),
            'subject' => 'Surat Terbit',
            'recipient' => 'Instansi C',
            'issued_at' => now(),
            'status' => 'active',
        ]);

        Livewire::test(Dashboard::class)
            ->assertSee('Ringkasan Persuratan')
            ->assertSee('Perlu Diproses')
            ->assertSee('Menunggu Persetujuan')
            ->assertSee('018/TEST/IX/2026');
    }
}
