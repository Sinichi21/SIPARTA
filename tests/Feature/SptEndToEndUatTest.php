<?php

namespace Tests\Feature;

use App\Enums\LetterStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\LetterService;
use App\Services\OutgoingLetterService;
use App\Services\SptReviewService;
use App\Services\SptSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SptEndToEndUatTest extends TestCase
{
    use RefreshDatabase;

    public function test_spt_identity_is_preserved_through_issued_letter_archive(): void
    {
        Storage::fake();

        foreach ([
            'letters.submit',
            'letters.verify',
            'letters.approve',
            'letters.publish',
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $user = User::factory()->create();
        $user->givePermissionTo([
            'letters.submit',
            'letters.verify',
            'letters.approve',
            'letters.publish',
        ]);
        $this->actingAs($user);

        $type = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'numbering_pattern' => '{sequence}/{type}/{month_roman}/{year}',
            'is_active' => true,
        ]);

        $activity = ActivityType::create([
            'name' => 'Monitoring UAT',
            'is_active' => true,
        ]);

        $draft = Letter::create([
            'letter_type_id' => $type->id,
            'activity_type_id' => $activity->id,
            'subject' => 'Monitoring frekuensi end-to-end',
            'letter_date' => '2026-10-05',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-06',
            'location' => 'Denpasar',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
            'status' => LetterStatus::Draft,
            'source' => 'system',
            'created_by' => $user->id,
        ]);

        $submitted = app(SptSubmissionService::class)->submit($draft, $user->id);
        $verified = app(SptReviewService::class)->verify($submitted, $user->id);
        $approved = app(SptReviewService::class)->approve($verified, $user->id);
        $publishedSpt = app(LetterService::class)->publish($approved, $user->id);

        $outgoing = OutgoingLetter::create([
            'source_spt_id' => $publishedSpt->id,
            'letter_type_id' => $type->id,
            'recipient' => 'Personil yang ditugaskan',
            'subject' => $publishedSpt->subject,
            'nature' => 'biasa',
            'content_html' => '<p>Surat Perintah Tugas</p>',
            'numbering_mode' => 'auto',
            'date_mode' => 'auto',
            'status' => OutgoingLetterStatus::Approved,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $issued = app(OutgoingLetterService::class)->publish($outgoing, $user)->fresh();
        $outgoing->refresh();

        $this->assertSame(OutgoingLetterStatus::Published, $outgoing->status);
        $this->assertSame($publishedSpt->number, $outgoing->number);
        $this->assertSame(
            $publishedSpt->letter_date?->toDateString(),
            $outgoing->letter_date?->toDateString()
        );

        $this->assertSame($publishedSpt->number, $issued->number);
        $this->assertSame(
            $publishedSpt->letter_date?->toDateString(),
            $issued->letter_date?->toDateString()
        );
        $this->assertSame($outgoing->id, $issued->outgoing_letter_id);
        $this->assertSame(
            $publishedSpt->id,
            data_get($issued->snapshot_json, 'source_spt_id')
        );

        $this->assertNotNull($issued->pdf_path);
        $this->assertNotNull($issued->file_sha256);
        $this->assertNotNull($issued->archived_document_at);
        Storage::assertExists($issued->pdf_path);

        $this->assertDatabaseHas('issued_letters', [
            'id' => $issued->id,
            'outgoing_letter_id' => $outgoing->id,
            'number' => $publishedSpt->number,
            'status' => 'active',
        ]);
    }
}
