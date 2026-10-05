<?php

namespace Tests\Feature;

use App\Enums\LetterStatus;
use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SptWorkflowDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_spt_workflow_counts_and_queue(): void
    {
        $user = User::factory()->create();

        foreach (['dashboard.view', 'letters.view'] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $user->givePermissionTo(['dashboard.view', 'letters.view']);
        $this->actingAs($user);

        $type = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'is_active' => true,
        ]);

        $activity = ActivityType::create([
            'name' => 'Monitoring Dashboard',
            'is_active' => true,
        ]);

        foreach ([
            LetterStatus::Submitted,
            LetterStatus::Verified,
            LetterStatus::Approved,
        ] as $index => $status) {
            Letter::create([
                'letter_type_id' => $type->id,
                'activity_type_id' => $activity->id,
                'subject' => 'Workflow SPT '.($index + 1),
                'letter_date' => today(),
                'start_date' => today(),
                'end_date' => today(),
                'location' => 'Denpasar',
                'status' => $status,
                'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
                'submission_reference' => 'REQ-SPT-TEST-'.($index + 1),
                'submitted_at' => now()->addMinutes($index),
                'created_by' => $user->id,
            ]);
        }

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Menunggu Verifikasi')
            ->assertSee('Menunggu Persetujuan')
            ->assertSee('Siap Diterbitkan')
            ->assertSee('REQ-SPT-TEST-1')
            ->assertSee('REQ-SPT-TEST-2')
            ->assertSee('REQ-SPT-TEST-3');
    }
}
