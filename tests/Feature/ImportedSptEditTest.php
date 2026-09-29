<?php

namespace Tests\Feature;

use App\Enums\LetterStatus;
use App\Livewire\Letters\Edit;
use App\Models\ActivityType;
use App\Models\AuditLog;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Personnel;
use App\Models\User;
use App\Services\LetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ImportedSptEditTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Letter $letter;

    private Personnel $person;

    private array $data;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        foreach (['letters.view', 'letters.update', 'reports.view', 'dashboard.view'] as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web']);
            $this->user->givePermissionTo($name);
        }
        $this->actingAs($this->user);
        $type = LetterType::create(['code' => 'SPT', 'name' => 'Surat Perintah Tugas', 'is_active' => true]);
        $activity = ActivityType::create(['name' => 'Monitoring', 'is_active' => true]);
        $this->person = Personnel::create(['name' => 'Personil Import', 'is_active' => true]);
        $this->data = [
            'number' => '123/SPT/2026', 'activity_type_id' => $activity->id,
            'subject' => 'Koreksi kegiatan', 'letter_date' => '2026-09-29',
            'start_date' => '2026-09-29', 'end_date' => '2026-09-30',
            'location' => 'Denpasar', 'basis' => 'Dokumen asli', 'description' => 'Koreksi hasil import',
            'record_type' => 'normal', 'personnel_ids' => [$this->person->id],
        ];
        $this->letter = Letter::create(array_merge($this->data, [
            'letter_type_id' => $type->id, 'source' => 'import', 'status' => 'published',
            'created_by' => $this->user->id, 'subject' => 'Perihal salah', 'published_at' => now()->subDay(),
        ]));
        $this->letter->personnels()->attach($this->person);
    }

    public function test_import_correction_preserves_status_and_records_changes(): void
    {
        $publishedAt = $this->letter->published_at;
        $updated = app(LetterService::class)->updateSpt($this->letter, $this->data, $this->user->id);
        $this->assertSame('Koreksi kegiatan', $updated->subject);
        $this->assertSame('import', $updated->source);
        $this->assertSame(LetterStatus::Published, $updated->status);
        $this->assertTrue($publishedAt->equalTo($updated->published_at));
        $audit = AuditLog::where('action', 'UPDATE')->firstOrFail();
        $this->assertSame('Perihal salah', $audit->old_values['subject']);
        $this->assertTrue($audit->new_values['import_correction']);
        $this->assertSame([$this->person->id], $audit->new_values['personnel_ids']);
    }

    public function test_import_edit_form_displays_warning_and_saves_corrections(): void
    {
        Livewire::test(Edit::class, ['letter' => $this->letter])
            ->assertSee('Perhatian: koreksi SPT hasil import')
            ->set('subject', 'Monitoring diperbaiki')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('letters.show', $this->letter));
        $this->assertSame('Monitoring diperbaiki', $this->letter->fresh()->subject);
    }

    public function test_existing_inactive_personnel_can_be_retained_in_import_corrections(): void
    {
        $this->person->update(['is_active' => false]);
        Livewire::test(Edit::class, ['letter' => $this->letter])->assertSee('Personil Import');
        $updated = app(LetterService::class)->updateSpt($this->letter, $this->data, $this->user->id);
        $this->assertSame([$this->person->id], $updated->personnels->pluck('id')->all());
    }

    public function test_new_inactive_personnel_cannot_be_added_to_an_import(): void
    {
        $inactive = Personnel::create(['name' => 'Nonaktif baru', 'is_active' => false]);
        $data = array_replace($this->data, ['personnel_ids' => [$inactive->id]]);
        $this->expectException(ValidationException::class);
        app(LetterService::class)->updateSpt($this->letter, $data, $this->user->id);
    }

    public function test_system_published_letter_stays_locked_even_with_a_stale_or_modified_model(): void
    {
        $this->letter->update(['source' => 'system']);
        $this->get(route('letters.edit', $this->letter))->assertForbidden();
        $this->letter->source = 'import';
        $this->expectException(ValidationException::class);
        app(LetterService::class)->updateSpt($this->letter, $this->data, $this->user->id);
    }

    public function test_import_edit_still_requires_update_permission(): void
    {
        $this->user->revokePermissionTo('letters.update');
        $this->get(route('letters.edit', $this->letter))->assertForbidden();
        $this->get(route('letters.show', $this->letter))->assertOk()->assertDontSee('Edit SPT');
    }

    public function test_system_drafts_remain_editable_and_full_detail_has_edit_action(): void
    {
        $this->letter->update(['source' => 'system', 'status' => 'draft']);
        $this->get(route('letters.show', $this->letter))->assertOk()
            ->assertSee('Informasi Penugasan')->assertSee('Daftar Personil')->assertSee('Edit SPT');
        $updated = app(LetterService::class)->updateSpt($this->letter, $this->data, $this->user->id);
        $this->assertSame(LetterStatus::Draft, $updated->status);
    }

    public function test_report_only_user_can_see_recap_navigation(): void
    {
        $this->user->revokePermissionTo('letters.view');
        $this->get(route('dashboard'))->assertOk()->assertSee('Rekap SPT')->assertDontSee('Master Data');
    }
}
