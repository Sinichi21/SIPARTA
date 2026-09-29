<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\PersonnelRecap\Index as PersonnelRecap;
use App\Livewire\SptRecap\Index as SptRecap;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RecapDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private LetterType $type;

    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
        $this->user = User::factory()->create();
        foreach (['reports.view', 'letters.view', 'letters.update', 'personnels.update', 'dashboard.view'] as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }
        $this->actingAs($this->user);
        $this->type = LetterType::create(['code' => 'SPT', 'name' => 'Surat Perintah Tugas', 'is_active' => true]);
    }

    private function letter(array $attributes = []): Letter
    {
        return Letter::create(array_merge([
            'letter_type_id' => $this->type->id,
            'created_by' => $this->user->id,
            'subject' => 'Monitoring Frekuensi',
            'letter_date' => today(),
            'start_date' => today(),
            'end_date' => today()->addDay(),
            'status' => 'published',
            'record_type' => 'normal',
        ], $attributes));
    }

    public function test_personnel_detail_counts_all_history_but_only_shows_latest_five(): void
    {
        $person = Personnel::create(['name' => 'Personil Uji', 'is_active' => true]);
        for ($i = 0; $i < 7; $i++) {
            $letter = $this->letter(['letter_date' => today()->subDays($i)]);
            $letter->personnels()->attach($person);
        }
        $correction = $this->letter(['record_type' => 'attendance_correction']);
        $correction->personnels()->attach($person);

        $component = app(PersonnelRecap::class);
        $component->showDetail($person->id);
        $data = $component->render()->getData();
        $this->assertSame(7, $data['selectedTotalSpt']);
        $this->assertCount(5, $data['selectedHistory']);
        $html = view('livewire.personnel-recap.detail', $data)->render();
        $this->assertStringContainsString('SPT Terakhir', $html);
        $this->assertStringContainsString('Riwayat SPT Terbaru', $html);
        $this->assertStringContainsString('Export Riwayat', $html);
    }

    public function test_empty_personnel_detail_renders_without_a_latest_letter(): void
    {
        $person = Personnel::create(['name' => 'Tanpa Penugasan', 'is_active' => false]);
        $component = app(PersonnelRecap::class);
        $component->showDetail($person->id);
        $data = $component->render()->getData();
        $this->assertSame(0, $data['selectedTotalSpt']);
        $html = view('livewire.personnel-recap.detail', $data)->render();
        $this->assertStringContainsString('Belum ada riwayat penugasan SPT.', $html);
        $this->assertStringContainsString('Tidak Aktif', $html);
    }

    public function test_spt_detail_respects_status_and_has_an_empty_document_state(): void
    {
        $letter = $this->letter()->load('personnels.unit', 'attachments', 'activityType');
        $html = view('livewire.spt-recap.detail', ['selectedLetter' => $letter])->render();
        $this->assertStringContainsString('Belum ada dokumen terlampir.', $html);
        $this->assertStringNotContainsString('Edit SPT', $html);
        $letter->status = 'draft';
        $html = view('livewire.spt-recap.detail', ['selectedLetter' => $letter])->render();
        $this->assertStringContainsString('Edit SPT', $html);
    }

    public function test_export_uses_personnel_history_and_escapes_spreadsheet_formulas(): void
    {
        $person = Personnel::create(['name' => 'Personil Uji']);
        $letter = $this->letter(['subject' => '=1+1', 'number' => 'SPT-001']);
        $letter->personnels()->attach($person);
        $this->letter(['number' => 'NOT-IN-HISTORY']);
        $component = app(PersonnelRecap::class);
        $component->showDetail($person->id);
        ob_start();
        $component->exportHistory()->sendContent();
        $csv = ob_get_clean();
        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringContainsString('SPT-001', $csv);
        $this->assertStringNotContainsString('NOT-IN-HISTORY', $csv);
    }

    public function test_export_requires_report_access(): void
    {
        $this->user->revokePermissionTo('reports.view');
        $this->expectException(AuthorizationException::class);
        app(PersonnelRecap::class)->exportHistory();
    }

    public function test_attachment_download_is_scoped_to_the_selected_spt(): void
    {
        Storage::fake();
        $selected = $this->letter();
        $other = $this->letter();
        $attachment = $other->attachments()->create([
            'original_name' => 'spt.pdf', 'stored_name' => 'spt.pdf',
            'path' => 'spt/spt.pdf', 'uploaded_by' => $this->user->id,
        ]);
        $component = app(SptRecap::class);
        $component->showDetail($selected->id);
        $this->expectException(ModelNotFoundException::class);
        $component->downloadAttachment($attachment->id);
    }

    public function test_missing_attachment_displays_a_download_error(): void
    {
        Storage::fake();
        $letter = $this->letter();
        $attachment = $letter->attachments()->create([
            'original_name' => 'missing.pdf', 'stored_name' => 'missing.pdf',
            'path' => 'spt/missing.pdf', 'uploaded_by' => $this->user->id,
        ]);
        $component = app(SptRecap::class);
        $component->showDetail($letter->id);
        $this->assertNull($component->downloadAttachment($attachment->id));
        $this->assertTrue($component->getErrorBag()->has('download'));
    }

    public function test_selected_attachment_can_be_downloaded(): void
    {
        Storage::fake();
        Storage::put('spt/document.pdf', 'document-content');
        $letter = $this->letter();
        $attachment = $letter->attachments()->create([
            'original_name' => 'document.pdf', 'stored_name' => 'document.pdf',
            'path' => 'spt/document.pdf', 'uploaded_by' => $this->user->id,
        ]);
        $component = app(SptRecap::class);
        $component->showDetail($letter->id);
        $response = $component->downloadAttachment($attachment->id);
        ob_start();
        $response->sendContent();
        $this->assertSame('document-content', ob_get_clean());
    }

    public function test_attachment_download_requires_letter_access(): void
    {
        $this->user->revokePermissionTo('letters.view');
        $this->expectException(AuthorizationException::class);
        app(SptRecap::class)->downloadAttachment(1);
    }

    public function test_dashboard_schedule_excludes_cancelled_expired_and_correction_letters(): void
    {
        $valid = $this->letter();
        $this->letter(['status' => 'cancelled']);
        $this->letter(['end_date' => today()->subDay()]);
        $this->letter(['record_type' => 'attendance_correction']);
        $data = app(Dashboard::class)->render()->getData();
        $this->assertSame([$valid->id], $data['scheduledLetters']->pluck('id')->all());
        $html = view('livewire.dashboard', $data)->render();
        $this->assertStringContainsString('Jadwal Tugas / SPT', $html);
        $this->assertStringContainsString('Aktivitas Terakhir', $html);
    }
}
