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
use Tests\TestCase;

class SptWorkflowAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_verification_approval_and_revision_write_audit_events(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $type = LetterType::create(['code' => 'SPT', 'name' => 'SPT', 'is_active' => true]);
        $activity = ActivityType::create(['name' => 'Monitoring Audit', 'is_active' => true]);
        $draft = Letter::create([
            'letter_type_id' => $type->id,
            'activity_type_id' => $activity->id,
            'subject' => 'Monitoring',
            'letter_date' => today(),
            'start_date' => today(),
            'end_date' => today(),
            'location' => 'Denpasar',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
            'status' => LetterStatus::Draft,
            'created_by' => $user->id,
        ]);

        $submitted = app(SptSubmissionService::class)->submit($draft, $user->id);
        $verified = app(SptReviewService::class)->verify($submitted, $user->id, 'Data lengkap');
        $this->assertDatabaseHas('audit_logs', [
            'subject_type' => Letter::class, 'subject_id' => $draft->id,
            'user_id' => $user->id, 'action' => 'SUBMIT',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'subject_type' => Letter::class, 'subject_id' => $draft->id,
            'user_id' => $user->id, 'action' => 'VERIFIED',
        ]);

        $returned = app(SptReviewService::class)->returnForRevision(
            $verified, $user->id, 'Perbaiki tanggal surat.'
        );
        $this->assertDatabaseHas('audit_logs', [
            'subject_type' => Letter::class, 'subject_id' => $draft->id,
            'user_id' => $user->id, 'action' => 'RETURN_FOR_REVISION',
        ]);
        $submittedAgain = app(SptSubmissionService::class)->submit($returned, $user->id);
        $verifiedAgain = app(SptReviewService::class)->verify($submittedAgain, $user->id);
        app(SptReviewService::class)->approve($verifiedAgain, $user->id, 'Disetujui');
        $this->assertDatabaseHas('audit_logs', [
            'subject_type' => Letter::class, 'subject_id' => $draft->id,
            'user_id' => $user->id, 'action' => 'APPROVED',
        ]);
    }
}
