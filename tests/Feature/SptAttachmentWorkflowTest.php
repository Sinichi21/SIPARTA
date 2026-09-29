<?php

namespace Tests\Feature;

use App\Livewire\Letters\Show;
use App\Models\ActivityType;
use App\Models\AuditLog;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SptAttachmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Letter $letter;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('letters.view', 'web');
        Permission::findOrCreate('letters.update', 'web');

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'letters.view',
            'letters.update',
        ]);
        $this->actingAs($this->user);

        $type = LetterType::create([
            'code' => 'SPT',
            'name' => 'Surat Perintah Tugas',
            'is_active' => true,
        ]);

        $activity = ActivityType::create([
            'code' => 'MONITORING',
            'name' => 'Monitoring Spektrum Frekuensi Radio',
            'is_active' => true,
        ]);

        $this->letter = Letter::create([
            'letter_type_id' => $type->id,
            'activity_type_id' => $activity->id,
            'created_by' => $this->user->id,
            'subject' => 'Monitoring Frekuensi',
            'letter_date' => today(),
            'start_date' => today(),
            'end_date' => today(),
            'location' => 'Denpasar',
            'status' => 'draft',
            'source' => 'system',
            'record_type' => 'normal',
            'personnel_scope' => Letter::PERSONNEL_SCOPE_ALL,
        ]);
    }

    public function test_editable_spt_can_upload_private_supporting_document_with_checksum(): void
    {
        Storage::fake();

        $component = app(Show::class);
        $component->letter = $this->letter->load('attachments');
        $component->attachmentUpload = UploadedFile::fake()
            ->create('dokumen-pendukung.pdf', 128, 'application/pdf');

        $component->uploadAttachment(app(AuditService::class));

        $attachment = $this->letter->attachments()->first();

        $this->assertNotNull($attachment);
        $this->assertSame('dokumen-pendukung.pdf', $attachment->original_name);
        $this->assertSame($this->user->id, $attachment->uploaded_by);
        $this->assertSame(64, strlen($attachment->checksum_sha256));
        Storage::assertExists($attachment->path);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'UPLOAD',
            'subject_type' => $attachment::class,
            'subject_id' => $attachment->id,
        ]);
    }

    public function test_attachment_delete_removes_file_database_row_and_writes_audit(): void
    {
        Storage::fake();

        Storage::put('spt/attachments/test/document.pdf', 'document');

        $attachment = $this->letter->attachments()->create([
            'original_name' => 'document.pdf',
            'stored_name' => 'document.pdf',
            'path' => 'spt/attachments/test/document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 8,
            'checksum_sha256' => hash('sha256', 'document'),
            'uploaded_by' => $this->user->id,
        ]);

        $attachmentId = $attachment->id;

        $component = app(Show::class);
        $component->letter = $this->letter->load('attachments');

        $component->deleteAttachment(
            $attachmentId,
            app(AuditService::class)
        );

        Storage::assertMissing('spt/attachments/test/document.pdf');
        $this->assertDatabaseMissing('letter_attachments', [
            'id' => $attachmentId,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'DELETE',
            'subject_type' => $attachment::class,
            'subject_id' => $attachmentId,
        ]);
    }

    public function test_published_system_spt_cannot_modify_attachments(): void
    {
        Storage::fake();

        $this->letter->update([
            'status' => 'published',
            'source' => 'system',
        ]);

        $component = app(Show::class);
        $component->letter = $this->letter->fresh()->load('attachments');
        $component->attachmentUpload = UploadedFile::fake()
            ->create('blocked.pdf', 32, 'application/pdf');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $component->uploadAttachment(app(AuditService::class));
    }
}
