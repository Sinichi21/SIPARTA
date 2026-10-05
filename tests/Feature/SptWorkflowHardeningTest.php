<?php

namespace Tests\Feature;

use App\Enums\LetterStatus;
use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Personnel;
use App\Models\User;
use App\Services\LetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SptWorkflowHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private LetterType $sptType;
    private ActivityType $activity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'letters.submit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'letters.verify', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'letters.approve', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'letters.publish', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'letters.cancel', 'guard_name' => 'web']);
        $this->user->givePermissionTo(["letters.submit","letters.verify","letters.approve","letters.publish","letters.cancel"]);
        $this->actingAs($this->user);

        $this->sptType = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'requires_personnel' => true,
            'is_active' => true,
        ]);

        $this->activity = ActivityType::create([
            'code' => 'MONITORING',
            'name' => 'Monitoring',
            'is_active' => true,
        ]);
    }

    private function letter(array $attributes = []): Letter
    {
        return Letter::create(array_merge([
            'letter_type_id' => $this->sptType->id,
            'activity_type_id' => $this->activity->id,
            'created_by' => $this->user->id,
            'number' => 'SPT-WF-001',
            'subject' => 'Monitoring Frekuensi',
            'letter_date' => today(),
            'start_date' => today(),
            'end_date' => today(),
            'location' => 'Denpasar',
            'status' => 'draft',
            'source' => 'system',
            'record_type' => 'normal',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_SELECTED,
        ], $attributes));
    }

    public function test_publish_transitions_draft_to_published_and_writes_audit(): void
    {
        $person = Personnel::create([
            'name' => 'Personil Uji',
            'is_active' => true,
        ]);

        $letter = $this->letter();
        $letter->personnels()->attach($person);

        $approved = app(\App\Services\SptReviewService::class)->approve(app(\App\Services\SptReviewService::class)->verify(app(\App\Services\SptSubmissionService::class)->submit($letter, $this->user->id), $this->user->id), $this->user->id);
        $published = app(LetterService::class)->publish(
            $approved,
            $this->user->id
        );

        $this->assertSame(LetterStatus::Published, $published->status);
        $this->assertNotNull($published->published_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'PUBLISH',
            'subject_type' => Letter::class,
            'subject_id' => $letter->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_published_spt_cannot_be_published_again(): void
    {
        $letter = $this->letter([
            'status' => 'published',
            'published_at' => now(),
            'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
        ]);

        $this->expectException(ValidationException::class);

        app(LetterService::class)->publish(
            $letter,
            $this->user->id
        );
    }

    public function test_selected_scope_without_personnel_cannot_be_published(): void
    {
        $letter = $this->letter();
        // An approved fixture checks the personnel guard independently of workflow status.
        $letter->forceFill(['status' => LetterStatus::Approved])->save();

        try {
            app(LetterService::class)->publish(
                $letter,
                $this->user->id
            );

            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'personnel_ids',
                $exception->errors()
            );
        }
    }

    public function test_all_personnel_scope_can_be_published_without_pivots(): void
    {
        $letter = $this->letter([
            'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
        ]);

        $approved = app(\App\Services\SptReviewService::class)->approve(app(\App\Services\SptReviewService::class)->verify(app(\App\Services\SptSubmissionService::class)->submit($letter, $this->user->id), $this->user->id), $this->user->id);
        $published = app(LetterService::class)->publish(
            $approved,
            $this->user->id
        );

        $this->assertSame(LetterStatus::Published, $published->status);
        $this->assertSame(0, $published->personnels()->count());
    }

    public function test_cancel_requires_reason_even_when_service_is_called_directly(): void
    {
        $letter = $this->letter([
            'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
        ]);

        try {
            app(LetterService::class)->cancel(
                $letter,
                'x',
                $this->user->id
            );

            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'cancellationReason',
                $exception->errors()
            );
        }
    }

    public function test_cancel_published_spt_records_reason_actor_and_audit(): void
    {
        $letter = $this->letter([
            'status' => 'published',
            'published_at' => now(),
            'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
        ]);

        $cancelled = app(LetterService::class)->cancel(
            $letter,
            'Kegiatan dibatalkan oleh pimpinan.',
            $this->user->id
        );

        $this->assertSame(LetterStatus::Cancelled, $cancelled->status);
        $this->assertSame(
            'Kegiatan dibatalkan oleh pimpinan.',
            $cancelled->cancellation_reason
        );
        $this->assertSame(
            $this->user->id,
            $cancelled->cancelled_by
        );
        $this->assertNotNull($cancelled->cancelled_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'CANCEL',
            'subject_type' => Letter::class,
            'subject_id' => $letter->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_cancelled_spt_cannot_be_cancelled_again(): void
    {
        $letter = $this->letter([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $this->user->id,
            'cancellation_reason' => 'Sudah dibatalkan.',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
        ]);

        $this->expectException(ValidationException::class);

        app(LetterService::class)->cancel(
            $letter,
            'Batalkan kembali.',
            $this->user->id
        );
    }

    public function test_letter_service_rejects_non_spt_for_publish_and_cancel(): void
    {
        $otherType = LetterType::create([
            'code' => 'UND',
            'name' => 'Undangan',
            'is_active' => true,
        ]);

        $letter = $this->letter([
            'letter_type_id' => $otherType->id,
            'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
        ]);

        try {
            app(LetterService::class)->publish(
                $letter,
                $this->user->id
            );

            $this->fail('Non-SPT should not be publishable through SPT service.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('letter_type', $exception->errors());
        }

        try {
            app(LetterService::class)->cancel(
                $letter,
                'Pembatalan valid.',
                $this->user->id
            );

            $this->fail('Non-SPT should not be cancellable through SPT service.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('letter_type', $exception->errors());
        }
    }
}
