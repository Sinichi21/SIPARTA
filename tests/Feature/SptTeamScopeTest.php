<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Personnel;
use App\Models\PersonnelTeam;
use App\Models\User;
use App\Services\LetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SptTeamScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_scope_snapshots_active_team_members_into_spt_personnel(): void
    {
        $user = User::factory()->create();

        LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'is_active' => true,
        ]);

        $activity = ActivityType::create([
            'code' => 'MON',
            'name' => 'Monitoring',
            'is_active' => true,
        ]);

        $first = Personnel::create(['name' => 'Personil A', 'is_active' => true]);
        $second = Personnel::create(['name' => 'Personil B', 'is_active' => true]);
        $inactive = Personnel::create(['name' => 'Personil Nonaktif', 'is_active' => false]);

        $team = PersonnelTeam::create([
            'name' => 'Tim Monitoring',
            'code' => 'MON',
            'is_active' => true,
        ]);

        $team->personnels()->sync([$first->id, $second->id, $inactive->id]);

        $letter = app(LetterService::class)->createSpt([
            'subject' => 'Monitoring Frekuensi',
            'activity_type_id' => $activity->id,
            'letter_date' => '2026-10-01',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-01',
            'location' => 'Denpasar',
            'record_type' => 'normal',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_TEAM,
            'personnel_team_id' => $team->id,
            'personnel_ids' => [],
        ], $user->id);

        $this->assertSame(Letter::PERSONNEL_SCOPE_TEAM, $letter->personnel_scope);
        $this->assertSame($team->id, $letter->personnel_team_id);
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $letter->personnels()->pluck('personnels.id')->all()
        );
    }
}
