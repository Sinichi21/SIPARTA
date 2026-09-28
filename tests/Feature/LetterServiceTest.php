<?php

namespace Tests\Feature;

use App\Enums\LetterStatus;
use App\Models\ActivityType;
use App\Models\LetterType;
use App\Models\Personnel;
use App\Models\User;
use App\Services\LetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'requires_personnel' => true,
            'is_active' => true,
        ]);
    }

    public function test_spt_can_be_created_with_multiple_personnel(): void
    {
        $user = User::factory()->create();

        $activityType = ActivityType::create([
            'code' => 'MONITORING',
            'name' => 'Monitoring',
            'is_active' => true,
        ]);

        $firstPersonnel = Personnel::create([
            'name' => 'Personil A',
            'is_active' => true,
        ]);

        $secondPersonnel = Personnel::create([
            'name' => 'Personil B',
            'is_active' => true,
        ]);

        $service = app(LetterService::class);

        $letter = $service->createSpt([
            'number' => null,
            'subject' => 'Monitoring Frekuensi',
            'activity_type_id' => $activityType->id,
            'letter_date' => '2026-09-29',
            'start_date' => '2026-09-29',
            'end_date' => '2026-09-30',
            'location' => 'Denpasar',
            'basis' => null,
            'description' => null,
            'personnel_ids' => [
                $firstPersonnel->id,
                $secondPersonnel->id,
            ],
        ], $user->id);

        $this->assertSame(
            LetterStatus::Draft,
            $letter->status
        );

        $this->assertCount(
            2,
            $letter->personnels
        );

        $this->assertDatabaseHas('letters', [
            'id' => $letter->id,
            'created_by' => $user->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseCount(
            'letter_personnel',
            2
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'CREATE',
            'subject_type' => \App\Models\Letter::class,
            'subject_id' => $letter->id,
        ]);
    }
}