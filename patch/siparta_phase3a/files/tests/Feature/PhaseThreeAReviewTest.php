<?php

namespace Tests\Feature;

use App\Enums\LetterStatus;
use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\User;
use App\Services\SptReviewService;
use App\Services\SptSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseThreeAReviewTest extends TestCase
{
    use RefreshDatabase;

    private function submittedSpt(): array
    {
        $user = User::factory()->create();
        $type = LetterType::create(['code' => 'SPT', 'name' => 'Surat Perintah Tugas', 'is_active' => true]);
        $activity = ActivityType::create(['name' => 'Monitoring Review', 'is_active' => true]);
        $letter = Letter::create([
            'letter_type_id' => $type->id, 'activity_type_id' => $activity->id,
            'subject' => 'Monitoring', 'start_date' => '2026-10-05',
            'end_date' => '2026-10-06', 'location' => 'Denpasar',
            'personnel_scope' => 'all', 'status' => LetterStatus::Draft, 'created_by' => $user->id,
        ]);
        return [app(SptSubmissionService::class)->submit($letter, $user->id), $user];
    }

    public function test_submitted_spt_can_be_verified_and_approved_without_numbering(): void
    {
        [$letter, $user] = $this->submittedSpt();
        $service = app(SptReviewService::class);
        $verified = $service->verify($letter, $user->id, 'Data lengkap');
        $this->assertSame(LetterStatus::Verified, $verified->status);
        $approved = $service->approve($verified, $user->id, 'Disetujui');
        $this->assertSame(LetterStatus::Approved, $approved->status);
        $this->assertNull($approved->number);
        $this->assertSame($user->id, $approved->approved_by);
        $this->assertDatabaseCount('spt_submission_events', 3);
    }

    public function test_return_for_revision_preserves_reference_and_allows_resubmission(): void
    {
        [$letter, $user] = $this->submittedSpt();
        $reference = $letter->submission_reference;
        $returned = app(SptReviewService::class)->returnForRevision($letter, $user->id, 'Mohon perbaiki lokasi kegiatan.');
        $this->assertSame(LetterStatus::Draft, $returned->status);
        $this->assertSame($reference, $returned->submission_reference);
        $submitted = app(SptSubmissionService::class)->submit($returned, $user->id);
        $this->assertSame($reference, $submitted->submission_reference);
        $this->assertDatabaseCount('spt_submission_events', 3);
    }

    public function test_approval_cannot_bypass_verification(): void
    {
        [$letter, $user] = $this->submittedSpt();
        $this->expectException(ValidationException::class);
        app(SptReviewService::class)->approve($letter, $user->id);
    }
}
