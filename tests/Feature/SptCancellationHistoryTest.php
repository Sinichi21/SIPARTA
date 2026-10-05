<?php

namespace Tests\Feature;

use App\Enums\LetterStatus;
use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\User;
use App\Services\LetterService;
use App\Services\SptReviewService;
use App\Services\SptSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SptCancellationHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function grantWorkflowPermissions(User $user): void
    {
        foreach ([
            'letters.submit',
            'letters.verify',
            'letters.approve',
            'letters.publish',
            'letters.cancel',
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $user->givePermissionTo([
            'letters.submit',
            'letters.verify',
            'letters.approve',
            'letters.publish',
            'letters.cancel',
        ]);
    }

    public function test_published_spt_cancellation_preserves_timeline_and_reason(): void
    {
        $user = User::factory()->create();
        $this->grantWorkflowPermissions($user);
        $this->actingAs($user);
        $type = LetterType::create(['code' => 'SPT', 'name' => 'SPT', 'is_active' => true]);
        $activity = ActivityType::create(['name' => 'Monitoring', 'is_active' => true]);
        $draft = Letter::create([
            'letter_type_id' => $type->id,
            'activity_type_id' => $activity->id,
            'subject' => 'Monitoring frekuensi',
            'letter_date' => today(),
            'start_date' => today(),
            'end_date' => today(),
            'location' => 'Denpasar',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
            'status' => LetterStatus::Draft,
            'created_by' => $user->id,
        ]);

        $submitted = app(SptSubmissionService::class)->submit($draft, $user->id);
        $verified = app(SptReviewService::class)->verify($submitted, $user->id);
        $approved = app(SptReviewService::class)->approve($verified, $user->id);
        $published = app(LetterService::class)->publish($approved, $user->id);
        $reason = 'Kegiatan dibatalkan berdasarkan arahan pimpinan.';
        $cancelled = app(LetterService::class)->cancel($published, $reason, $user->id);

        $this->assertSame(LetterStatus::Cancelled, $cancelled->status);
        $this->assertSame($published->number, $cancelled->number);
        $this->assertSame($submitted->submission_reference, $cancelled->submission_reference);
        $this->assertDatabaseHas('spt_submission_events', [
            'letter_id' => $draft->id,
            'actor_id' => $user->id,
            'event' => 'cancelled',
            'from_status' => LetterStatus::Published->value,
            'to_status' => LetterStatus::Cancelled->value,
            'note' => $reason,
        ]);
        $this->assertDatabaseCount('spt_submission_events', 5);
    }

    public function test_legacy_draft_without_submission_reference_has_no_workflow_event(): void
    {
        $user = User::factory()->create();
        $this->grantWorkflowPermissions($user);
        $this->actingAs($user);
        $type = LetterType::create(['code' => 'SPT', 'name' => 'SPT', 'is_active' => true]);
        $letter = Letter::create([
            'letter_type_id' => $type->id,
            'status' => LetterStatus::Draft,
            'subject' => 'Dokumen lama',
            'created_by' => $user->id,
        ]);

        app(LetterService::class)->cancel($letter, 'Dokumen lama dibatalkan.', $user->id);
        $this->assertDatabaseCount('spt_submission_events', 0);
    }
}
