<?php

namespace Tests\Feature;

use App\Enums\LetterStatus;
use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\User;
use App\Services\SptSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PhaseTwoBSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_all_personnel_can_be_submitted_once_without_official_number(): void
    {
        $user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'letters.submit', 'guard_name' => 'web']);
        $user->givePermissionTo(["letters.submit"]);
        $type = LetterType::create(['code' => 'SPT', 'name' => 'Surat Perintah Tugas', 'is_active' => true]);
        $activity = ActivityType::create(['name' => 'Monitoring', 'is_active' => true]);
        $letter = Letter::create([
            'letter_type_id' => $type->id, 'activity_type_id' => $activity->id,
            'subject' => 'Monitoring frekuensi', 'start_date' => '2026-10-05',
            'end_date' => '2026-10-06', 'location' => 'Denpasar',
            'personnel_scope' => 'all', 'status' => LetterStatus::Draft,
            'created_by' => $user->id,
        ]);
        $result = app(SptSubmissionService::class)->submit($letter, $user->id);
        $this->assertSame(LetterStatus::Submitted, $result->status);
        $this->assertSame('REQ-SPT-'.$letter->created_at->format('Y').'-'.str_pad((string) $letter->id, 6, '0', STR_PAD_LEFT), $result->submission_reference);
        $this->assertNull($result->number);
        $this->assertDatabaseCount('spt_submission_events', 1);
        try {
            app(SptSubmissionService::class)->submit($letter, $user->id);
            $this->fail('Submission ulang seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertDatabaseCount('spt_submission_events', 1);
        }
    }
}
