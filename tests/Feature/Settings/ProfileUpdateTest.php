<?php

namespace Tests\Feature\Settings;

use App\Models\Personnel;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $user->refresh();

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_update_only_their_linked_employment_record(): void
    {
        $person = Personnel::create(['name' => 'Nama Lama', 'nip' => '1001', 'is_active' => true]);
        $other = Personnel::create(['name' => 'Personil Lain', 'nip' => '1002', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Tim Monitoring', 'is_active' => true]);
        $user = User::factory()->create();
        $user->forceFill(['personnel_id' => $person->id])->save();
        $this->actingAs($user);
        Livewire::test('pages::settings.profile')
            ->set('employment.name', 'Nama Diperbarui')
            ->set('employment.nip', '1003')
            ->set('employment.unit_id', $unit->id)
            ->set('employment.position', 'Analis')
            ->set('employment.rank', 'Penata')
            ->set('employment.grade', 'III/c')
            ->set('employment.email', 'staff@example.com')
            ->set('employment.phone', '08123456789')
            ->set('employment.id', $other->id)
            ->set('employment.is_active', false)
            ->call('updateEmployment')->assertHasNoErrors();
        $this->assertSame('Nama Diperbarui', $person->fresh()->name);
        $this->assertSame($unit->id, $person->fresh()->unit_id);
        $this->assertSame('III/c', $person->fresh()->grade);
        $this->assertTrue($person->fresh()->is_active);
        $this->assertSame('Personil Lain', $other->fresh()->name);
        $this->assertDatabaseHas('audit_logs', ['subject_id' => $person->id, 'subject_type' => Personnel::class, 'action' => 'UPDATE']);
        Livewire::test('pages::settings.profile')->set('employment.nip', '1002')
            ->call('updateEmployment')->assertHasErrors(['employment.nip']);
    }

    public function test_unlinked_user_cannot_claim_a_personnel_record(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test('pages::settings.profile')->set('employment.name', 'Nama Baru')
            ->call('updateEmployment')->assertForbidden();
        $this->assertDatabaseCount('personnels', 0);
    }

    public function test_profile_photo_can_be_uploaded_replaced_and_removed(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user);
        Livewire::test('pages::settings.profile')
            ->set('photo', UploadedFile::fake()->image('portrait.jpg', 200, 200))
            ->call('savePhoto')->assertHasNoErrors()->assertRedirect(route('profile.edit'));
        $old = $user->fresh()->profile_photo_path;
        Storage::disk('local')->assertExists($old);
        $this->get(route('profile.photo'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        Livewire::test('pages::settings.profile')
            ->set('photo', UploadedFile::fake()->image('new.png', 200, 200))
            ->call('savePhoto')->assertHasNoErrors();
        Storage::disk('local')->assertMissing($old);
        $new = $user->fresh()->profile_photo_path;
        Livewire::test('pages::settings.profile')->call('removePhoto')->assertHasNoErrors();
        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk('local')->assertMissing($new);
    }

    public function test_invalid_photos_are_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach ([UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'), UploadedFile::fake()->image('large.jpg')->size(2049)] as $photo) {
            Livewire::test('pages::settings.profile')->set('photo', $photo)->call('savePhoto')->assertHasErrors(['photo']);
        }
        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'password')
            ->call('deleteUser');

        $response
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($user->fresh());
        $this->assertFalse(auth()->check());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $response->assertHasErrors(['password']);

        $this->assertNotNull($user->fresh());
    }
}
