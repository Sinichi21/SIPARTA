<?php

namespace Tests\Feature;

use App\Livewire\MySpt\Report;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Personnel;
use App\Models\SptReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SptReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_participant_can_report_for_every_participant_without_duplicate_report(): void
    {
        [$firstUser, $firstPersonnel] = $this->staff('Pelapor Pertama');
        [$secondUser, $secondPersonnel] = $this->staff('Peserta Kedua');

        $letter = $this->completedSpt($firstUser);
        $letter->personnels()->attach([
            $firstPersonnel->id,
            $secondPersonnel->id,
        ]);

        Livewire::actingAs($firstUser)
            ->test(Report::class, ['letter' => $letter])
            ->set('activity_summary', 'Pelaksanaan kegiatan berjalan sesuai dengan surat tugas.')
            ->set('results', 'Kegiatan menghasilkan data dan dokumentasi yang dibutuhkan.')
            ->set('obstacles', 'Tidak terdapat kendala yang berarti.')
            ->set('follow_up', 'Hasil kegiatan akan ditindaklanjuti oleh unit terkait.')
            ->call('saveDraft')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('spt_reports', 1);
        $this->assertDatabaseHas('spt_reports', [
            'letter_id' => $letter->id,
            'created_by' => $firstUser->id,
            'status' => SptReport::STATUS_DRAFT,
        ]);

        Livewire::actingAs($secondUser)
            ->test(Report::class, ['letter' => $letter])
            ->assertSee('Draft laporan sedang disusun oleh Pelapor Pertama')
            ->set('activity_summary', 'Peserta kedua mencoba membuat laporan lain.')
            ->set('results', 'Laporan lain tidak boleh dibuat untuk SPT yang sama.')
            ->call('saveDraft')
            ->assertHasErrors(['report']);

        $this->assertDatabaseCount('spt_reports', 1);

        Livewire::actingAs($firstUser)
            ->test(Report::class, ['letter' => $letter])
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('Sudah Dilaporkan');

        $this->assertDatabaseHas('spt_reports', [
            'letter_id' => $letter->id,
            'status' => SptReport::STATUS_SUBMITTED,
            'submitted_by' => $firstUser->id,
        ]);

        $this->actingAs($secondUser)
            ->get(route('my-spt.report', $letter))
            ->assertOk()
            ->assertSee('berlaku untuk seluruh peserta SPT');
    }

    public function test_pdf_supporting_documents_are_private_and_locked_after_submit(): void
    {
        Storage::fake('local');

        [$user, $personnel] = $this->staff('Pelapor Lampiran');

        $letter = $this->completedSpt($user);
        $letter->personnels()->attach($personnel);

        Livewire::actingAs($user)
            ->test(Report::class, ['letter' => $letter])
            ->set('activity_summary', 'Pelaksanaan kegiatan berjalan sesuai dengan surat tugas.')
            ->set('results', 'Kegiatan menghasilkan data dan dokumentasi yang dibutuhkan.')
            ->call('saveDraft')
            ->assertHasNoErrors()
            ->set('attachments', [
                UploadedFile::fake()->create(
                    'bukti-kegiatan.pdf',
                    512,
                    'application/pdf'
                ),
            ])
            ->call('uploadAttachments')
            ->assertHasNoErrors()
            ->assertSee('bukti-kegiatan.pdf');

        $attachment = \App\Models\SptReportAttachment::query()->firstOrFail();

        Storage::disk('local')->assertExists($attachment->path);

        $this->actingAs($user)
            ->get(route('my-spt-report-attachments.download', $attachment))
            ->assertOk();

        Livewire::actingAs($user)
            ->test(Report::class, ['letter' => $letter])
            ->call('submit')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test(Report::class, ['letter' => $letter])
            ->call('deleteAttachment', $attachment->id)
            ->assertHasErrors(['attachments']);

        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_all_personnel_spt_is_visible_and_reportable_without_personnel_pivot(): void
    {
        [$user] = $this->staff('Staff Seluruh Pegawai');

        $letter = $this->completedSpt(
            $user,
            Letter::PERSONNEL_SCOPE_ALL
        );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Belum Dilaporkan')
            ->assertSee($letter->number);

        $this->get(route('my-spt.show', $letter))
            ->assertOk()
            ->assertSee('Buat Laporan SKP');

        $this->get(route('my-spt.report', $letter))
            ->assertOk()
            ->assertSee('Satu laporan mewakili seluruh peserta');
    }

    public function test_imported_spt_does_not_require_new_skp_report(): void
    {
        [$user, $personnel] = $this->staff('Staff Arsip');

        $letter = $this->completedSpt($user);
        $letter->forceFill(['source' => 'import'])->save();
        $letter->personnels()->attach($personnel);

        $this->assertFalse($letter->fresh()->requiresSptReport());

        $this->actingAs($user)
            ->get(route('my-spt.report', $letter))
            ->assertNotFound();
    }

    private function staff(string $name): array
    {
        $personnel = Personnel::create([
            'name' => $name,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'name' => $name,
        ]);

        $user->forceFill([
            'personnel_id' => $personnel->id,
        ])->save();

        foreach (['my-dashboard.view', 'my-letters.view', 'my-reports.view'] as $permission) {
            $user->givePermissionTo(
                Permission::findOrCreate($permission, 'web')
            );
        }

        return [$user, $personnel];
    }

    private function completedSpt(
        User $creator,
        string $scope = Letter::PERSONNEL_SCOPE_SELECTED
    ): Letter {
        $type = LetterType::firstOrCreate(
            ['code' => 'SPT'],
            [
                'name' => 'Surat Perintah Tugas',
                'is_active' => true,
            ]
        );

        return Letter::create([
            'letter_type_id' => $type->id,
            'number' => 'SPT-SKP-'.fake()->unique()->numerify('####'),
            'subject' => 'Pengujian laporan SKP',
            'letter_date' => today()->subDays(3),
            'start_date' => today()->subDays(2),
            'end_date' => today()->subDay(),
            'location' => 'Denpasar',
            'status' => 'published',
            'source' => 'system',
            'personnel_scope' => $scope,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]);
    }
}
