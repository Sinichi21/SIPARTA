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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SptServiceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private LetterType $type;
    private ActivityType $activity;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->type = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'is_active' => true,
        ]);

        $this->activity = ActivityType::create([
            'name' => 'Monitoring Authorization',
            'is_active' => true,
        ]);
    }

    private function draft(User $user): Letter
    {
        return Letter::create([
            'letter_type_id' => $this->type->id,
            'activity_type_id' => $this->activity->id,
            'subject' => 'Monitoring frekuensi',
            'letter_date' => '2026-10-05',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-06',
            'location' => 'Denpasar',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
            'status' => LetterStatus::Draft,
            'created_by' => $user->id,
        ]);
    }

    public function test_submit_service_rejects_actor_without_permission(): void
    {
        $user = User::factory()->create();
        $letter = $this->draft($user);

        $this->expectException(AuthorizationException::class);

        app(SptSubmissionService::class)->submit($letter, $user->id);
    }

    public function test_review_services_reject_actor_without_required_permissions(): void
    {
        $submitter = User::factory()->create();
        $submitter->givePermissionTo('letters.submit');

        $submitted = app(SptSubmissionService::class)
            ->submit($this->draft($submitter), $submitter->id);

        $reviewer = User::factory()->create();

        try {
            app(SptReviewService::class)->verify($submitted, $reviewer->id);
            $this->fail('Verify without permission should be rejected.');
        } catch (AuthorizationException) {
            $this->assertSame(LetterStatus::Submitted, $submitted->fresh()->status);
        }

        $reviewer->givePermissionTo('letters.verify');
        $verified = app(SptReviewService::class)
            ->verify($submitted, $reviewer->id);

        try {
            app(SptReviewService::class)->approve($verified, $reviewer->id);
            $this->fail('Approve without permission should be rejected.');
        } catch (AuthorizationException) {
            $this->assertSame(LetterStatus::Verified, $verified->fresh()->status);
        }
    }

    public function test_publish_and_cancel_services_enforce_permissions(): void
    {
        $actor = User::factory()->create();
        $letter = $this->draft($actor);

        $letter->forceFill([
            'status' => LetterStatus::Approved,
            'submission_reference' => 'REQ-SPT-2026-000001',
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ])->save();

        try {
            app(LetterService::class)->publish($letter, $actor->id);
            $this->fail('Publish without permission should be rejected.');
        } catch (AuthorizationException) {
            $this->assertSame(LetterStatus::Approved, $letter->fresh()->status);
        }

        $draft = $this->draft($actor);

        $this->expectException(AuthorizationException::class);

        app(LetterService::class)->cancel(
            $draft,
            'Pembatalan untuk pengujian otorisasi.',
            $actor->id
        );
    }

    public function test_authorized_actor_can_complete_workflow(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            'letters.submit',
            'letters.verify',
            'letters.approve',
            'letters.publish',
        ]);

        $submitted = app(SptSubmissionService::class)
            ->submit($this->draft($user), $user->id);
        $verified = app(SptReviewService::class)
            ->verify($submitted, $user->id);
        $approved = app(SptReviewService::class)
            ->approve($verified, $user->id);
        $published = app(LetterService::class)
            ->publish($approved, $user->id);

        $this->assertSame(LetterStatus::Published, $published->status);
        $this->assertNotNull($published->number);
    }
}
