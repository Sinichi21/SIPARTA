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
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseThreeBPublishingTest extends TestCase
{
    use RefreshDatabase;

    private function makeDraft(User $user, string $source = 'manual'): Letter
    {
        $type = LetterType::firstOrCreate(['code' => 'SPT'], ['name' => 'Surat Perintah Tugas', 'is_active' => true]);
        $activity = ActivityType::firstOrCreate(['name' => 'Monitoring Publish'], ['is_active' => true]);

        return Letter::create([
            'letter_type_id' => $type->id,
            'activity_type_id' => $activity->id,
            'subject' => 'Monitoring frekuensi',
            'letter_date' => '2026-10-05',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-06',
            'location' => 'Denpasar',
            'personnel_scope' => 'all',
            'status' => LetterStatus::Draft,
            'source' => $source,
            'created_by' => $user->id,
        ]);
    }

    private function assertPublishRejected(Letter $letter, User $user): void
    {
        try {
            app(LetterService::class)->publish($letter, $user->id);
            $this->fail('Penerbitan sebelum persetujuan seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $letter->refresh();
            $this->assertNull($letter->published_at);
            $this->assertNull($letter->number);
        }
    }

    public function test_draft_submitted_and_verified_cannot_be_published(): void
    {
        $user = User::factory()->create();
        $letter = $this->makeDraft($user);
        $this->assertPublishRejected($letter, $user);

        $submitted = app(SptSubmissionService::class)->submit($letter, $user->id);
        $this->assertPublishRejected($submitted, $user);

        $verified = app(SptReviewService::class)->verify($submitted, $user->id);
        $this->assertPublishRejected($verified, $user);
    }

    public function test_only_approved_spt_receives_official_number_on_publication(): void
    {
        $user = User::factory()->create();
        $submitted = app(SptSubmissionService::class)->submit($this->makeDraft($user), $user->id);
        $verified = app(SptReviewService::class)->verify($submitted, $user->id);
        $approved = app(SptReviewService::class)->approve($verified, $user->id);
        $this->assertNull($approved->number);
        $this->assertNull($approved->published_at);

        $published = app(LetterService::class)->publish($approved, $user->id);
        $this->assertSame(LetterStatus::Published, $published->status);
        $this->assertNotNull($published->number);
        $this->assertNotNull($published->published_at);
        $this->assertSame($approved->submission_reference, $published->submission_reference);
        $this->assertSame($user->id, $published->approved_by);
        $this->assertDatabaseCount('spt_submission_events', 3);

        try {
            app(LetterService::class)->publish($published, $user->id);
            $this->fail('Penerbitan ulang seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame($published->number, $published->fresh()->number);
        }
    }

    public function test_imported_spt_cannot_enter_new_publication_flow(): void
    {
        $user = User::factory()->create();
        $letter = $this->makeDraft($user, 'import');
        $letter->forceFill([
            'status' => LetterStatus::Approved,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ])->save();

        $this->assertPublishRejected($letter, $user);
    }
}
