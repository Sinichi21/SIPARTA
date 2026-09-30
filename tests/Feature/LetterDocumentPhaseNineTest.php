<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Letter;
use App\Models\LetterheadProfile;
use App\Models\LetterTemplate;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\Personnel;
use App\Models\User;
use App\Services\LetterDocumentService;
use App\Services\LetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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

    public function test_linked_spt_uses_outgoing_personnel_table_for_preview_print_and_pdf(): void
    {
        Gate::define('letters.view', fn () => true);
        $letter = $this->draft();
        $outgoing = $this->linkedOutgoing($letter);
        $outgoing->personnels()->sync($letter->personnels->modelKeys());

        foreach (['preview', 'print'] as $action) {
            $this->get(route('letters.document.'.$action, $letter))
                ->assertOk()
                ->assertViewIs('outgoing-letters.document-preview')
                ->assertSee('Isi surat keluar yang dipilih')
                ->assertSee('Personil A')
                ->assertSee('Jabatan');
        }

        $this->get(route('letters.document.pdf', $letter))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertNull($letter->documentSnapshot()->first());
        $this->get(route('letters.show', $letter))->assertOk()->assertSee('Surat SPT terkait');
    }

    public function test_linked_issued_pdf_uses_exact_archive_and_never_regenerates_missing_files(): void
    {
        Gate::define('letters.view', fn () => true);
        Storage::fake();
        $letter = $this->draft();
        $outgoing = $this->linkedOutgoing($letter);
        $binary = '%PDF-1.4 immutable official archive';
        Storage::put('issued/test.pdf', $binary);
        $outgoing->issuedLetter()->create([
            'letter_type_id' => $this->type->id,
            'number' => '001/SPT/2026', 'letter_date' => '2026-09-30',
            'subject' => 'Surat resmi', 'recipient' => 'Personil', 'issued_at' => now(),
            'pdf_path' => 'issued/test.pdf', 'file_sha256' => hash('sha256', $binary),
        ]);
        $outgoing->update(['content_html' => '<p>Isi berubah setelah terbit</p>']);

        $this->get(route('letters.document.pdf', $letter))->assertOk()->assertContent($binary);
        $this->get(route('letters.document.pdf', ['letter' => $letter, 'inline' => 1]))
            ->assertOk()->assertContent($binary)
            ->assertHeader('Content-Disposition', 'inline; filename="SPT-001spt2026.pdf"');
        foreach (['preview', 'print'] as $action) {
            $this->get(route('letters.document.'.$action, $letter))
                ->assertOk()->assertViewIs('letters.linked-document-preview')
                ->assertDontSee('Isi berubah setelah terbit');
        }
        Storage::put('issued/test.pdf', 'file altered');
        $this->getJson(route('letters.document.pdf', $letter))
            ->assertUnprocessable()->assertJsonValidationErrors('document');
        Storage::delete('issued/test.pdf');
        $this->get(route('letters.document.pdf', $letter))->assertNotFound();
        $this->assertNull($letter->documentSnapshot()->first());
    }

    public function test_linked_documents_still_require_spt_permission(): void
    {
        Gate::define('letters.view', fn () => false);
        $letter = $this->draft();
        $this->linkedOutgoing($letter);
        foreach (['preview', 'print', 'pdf'] as $action) {
            $this->get(route('letters.document.'.$action, $letter))->assertForbidden();
        }
    }

    private function linkedOutgoing(Letter $letter): OutgoingLetter
    {
        return OutgoingLetter::create([
            'source_spt_id' => $letter->id,
            'letter_type_id' => $this->type->id,
            'subject' => 'Surat terkait', 'recipient' => 'Personil', 'nature' => 'biasa',
            'content_html' => '<p>Isi surat keluar yang dipilih</p>{{personil}}',
            'status' => 'draft', 'created_by' => $this->user->id,
        ]);
    }
}
