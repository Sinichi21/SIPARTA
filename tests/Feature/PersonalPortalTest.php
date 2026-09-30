<?php

namespace Tests\Feature;

use App\Livewire\MySpt\Index;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PersonalPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_pages_render_and_keep_other_personnel_letters_private(): void
    {
        $user = $this->staff();
        $person = Personnel::create(['name' => 'Staff Uji', 'is_active' => true]);
        $user->forceFill(['personnel_id' => $person->id])->save();
        $type = LetterType::create(['code' => 'SPT', 'name' => 'Surat Perintah Tugas', 'is_active' => true]);
        $attributes = ['letter_type_id' => $type->id, 'created_by' => $user->id, 'letter_date' => today(), 'status' => 'published'];
        $own = Letter::create($attributes + ['number' => 'SPT-MILIK-SAYA', 'subject' => 'Penugasan saya']);
        $own->personnels()->attach($person);
        $other = Letter::create($attributes + ['number' => 'SPT-RAHASIA-LAIN', 'subject' => 'Penugasan lain']);

        foreach (['dashboard', 'my-spt.index', 'my-recap.index'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('SPT-MILIK-SAYA')->assertDontSee('SPT-RAHASIA-LAIN');
        }
        $this->get(route('my-spt.show', $own))->assertOk()->assertSee('Detail SPT Saya');
        $this->get(route('my-spt.show', $other))->assertNotFound();
    }

    public function test_url_filters_and_reset_work_for_an_unlinked_staff_account(): void
    {
        $this->staff();
        Livewire::withQueryParams(['year' => '2024', 'search' => 'Monitoring'])
            ->test(Index::class)
            ->assertSet('year', '2024')
            ->assertSet('search', 'Monitoring')
            ->assertSee('Akun belum terhubung ke data personil')
            ->call('resetFilters')
            ->assertSet('year', (string) now()->year)
            ->assertSet('search', '');
        Livewire::withQueryParams(['year' => ''])->test(Index::class)->assertSet('year', '');
    }

    private function staff(): User
    {
        $user = User::factory()->create();
        foreach (['my-dashboard.view', 'my-letters.view', 'my-reports.view'] as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $this->actingAs($user);

        return $user;
    }
}
