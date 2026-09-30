<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\LetterType;
use App\Models\LetterheadProfile;
use App\Models\Personnel;
use App\Models\User;
use App\Services\LetterDocumentService;
use App\Services\LetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterDocumentPhaseNineTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private LetterType $type;
    private ActivityType $activity;
    private LetterheadProfile $profile;
    private LetterTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->type = LetterType::create([
            'code' => 'SPT', 'name' => 'Surat Perintah Tugas',
            'requires_personnel' => true, 'is_active' => true,
        ]);

        $this->activity = ActivityType::create([
            'code' => 'MON', 'name' => 'Monitoring', 'is_active' => true,
        ]);

        $this->profile = LetterheadProfile::create([
            'name' => 'Kop Utama', 'organization_name' => 'Balai Monitor Lama',
            'city' => 'Denpasar', 'signatory_name' => 'Pejabat Lama',
            'signatory_position' => 'Kepala Balai', 'is_default' => true,
            'is_active' => true, 'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->template = LetterTemplate::create([
            'letter_type_id' => $this->type->id,
            'letterhead_profile_id' => $this->profile->id,
            'name' => 'Template SPT', 'code' => 'SPT_DEFAULT',
            'content_html' => '<p>{{kegiatan}}</p><p>{{instansi}}</p>',
            'version' => 1, 'is_default' => true, 'is_active' => true,
            'created_by' => $this->user->id, 'updated_by' => $this->user->id,
        ]);
    }

    private function draft(): Letter
    {
        $person = Personnel::create(['name' => 'Personil A', 'is_active' => true]);

        $letter = Letter::create([
            'letter_type_id' => $this->type->id,
            'activity_type_id' => $this->activity->id,
            'subject' => 'Monitoring Frekuensi', 'letter_date' => '2026-09-30',
            'start_date' => '2026-09-30', 'end_date' => '2026-09-30',
            'location' => 'Denpasar', 'status' => 'draft', 'record_type' => 'normal',
            'personnel_scope' => 'selected', 'created_by' => $this->user->id,
        ]);

        $letter->personnels()->attach($person);
        return $letter;
    }

    public function test_publish_creates_immutable_document_snapshot(): void
    {
        $published = app(LetterService::class)->publish($this->draft(), $this->user->id);
        $snapshot = $published->documentSnapshot()->first();

        $this->assertNotNull($snapshot);
        $this->assertSame(64, strlen($snapshot->checksum_sha256));
        $this->assertStringContainsString('Balai Monitor Lama', $snapshot->rendered_html);

        $this->template->update(['content_html' => '<p>ISI BARU</p>', 'version' => 2]);
        $this->profile->update(['organization_name' => 'Balai Monitor Baru', 'signatory_name' => 'Pejabat Baru']);

        $payload = app(LetterDocumentService::class)->payloadFor($published->fresh(), $this->user->id);

        $this->assertTrue($payload['is_snapshot']);
        $this->assertStringContainsString('Balai Monitor Lama', $payload['rendered_html']);
        $this->assertStringNotContainsString('ISI BARU', $payload['rendered_html']);
        $this->assertSame('Pejabat Lama', $payload['letterhead']['signatory_name']);
    }

    public function test_draft_preview_is_live_without_snapshot(): void
    {
        $letter = $this->draft();
        $payload = app(LetterDocumentService::class)->payloadFor($letter, $this->user->id);

        $this->assertFalse($payload['is_snapshot']);
        $this->assertNull($letter->documentSnapshot()->first());
        $this->assertStringContainsString('Monitoring Frekuensi', $payload['rendered_html']);
    }

    public function test_legacy_published_letter_gets_snapshot_on_first_access(): void
    {
        $letter = $this->draft();
        $letter->update(['status' => 'published', 'number' => '001/SPT/IX/2026', 'published_at' => now()]);

        $payload = app(LetterDocumentService::class)->payloadFor($letter->fresh(), $this->user->id);

        $this->assertTrue($payload['is_snapshot']);
        $this->assertTrue($payload['legacy_generated']);
        $this->assertDatabaseHas('letter_document_snapshots', ['letter_id' => $letter->id]);
    }
}
