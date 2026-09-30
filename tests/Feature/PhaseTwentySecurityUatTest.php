<?php

namespace Tests\Feature;

use App\Livewire\OutgoingLetters\Show as OutgoingLetterShow;
use App\Models\IssuedLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PhaseTwentySecurityUatTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_internal_correspondence_routes(): void
    {
        foreach ([
            'incoming-letters.index',
            'outgoing-letters.index',
            'issued-letters.index',
            'correspondence-register.index',
            'letters.index',
        ] as $routeName) {
            $this->get(route($routeName))
                ->assertRedirect(route('login'));
        }
    }

    public function test_authenticated_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        foreach ([
            'incoming-letters.index',
            'outgoing-letters.index',
            'issued-letters.index',
            'correspondence-register.index',
        ] as $routeName) {
            $this->get(route($routeName))
                ->assertForbidden();
        }
    }

    public function test_view_only_user_cannot_verify_outgoing_letter(): void
    {
        foreach ([
            'outgoing-letters.view',
            'outgoing-letters.verify',
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $user = User::factory()->create();
        $user->givePermissionTo('outgoing-letters.view');

        $letter = OutgoingLetter::create([
            'recipient' => 'Instansi A',
            'subject' => 'Uji Akses',
            'nature' => 'biasa',
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(OutgoingLetterShow::class, [
            'letter' => $letter,
        ])
            ->call('verify')
            ->assertForbidden();

        $this->assertSame(
            'draft',
            $letter->fresh()->status->value
        );
    }

    public function test_public_verification_rejects_malformed_code(): void
    {
        $this->get(route('issued-letters.verify', [
            'code' => 'not-a-valid-code',
        ]))->assertNotFound();
    }

    public function test_public_verification_works_without_authentication(): void
    {
        $creator = User::factory()->create();

        $outgoing = OutgoingLetter::create([
            'recipient' => 'Instansi B',
            'subject' => 'Dokumen Terbit',
            'nature' => 'biasa',
            'status' => 'published',
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]);

        $code = str_repeat('a', 48);

        $issued = IssuedLetter::create([
            'outgoing_letter_id' => $outgoing->id,
            'number' => '001/SEC/2026',
            'letter_date' => '2026-09-30',
            'subject' => 'Dokumen Terbit',
            'recipient' => 'Instansi B',
            'issued_at' => now(),
            'issued_by' => $creator->id,
            'status' => 'active',
            'verification_code' => $code,
        ]);

        $this->get(route('issued-letters.verify', [
            'code' => $code,
        ]))
            ->assertOk()
            ->assertSee($issued->number);

        $this->assertSame(
            1,
            $issued->fresh()->verification_count
        );
    }

    public function test_public_verification_route_is_rate_limited(): void
    {
        $route = collect(Route::getRoutes())
            ->first(fn ($route) => $route->getName() === 'issued-letters.verify');

        $this->assertNotNull($route);

        $this->assertContains(
            'throttle:60,1',
            $route->gatherMiddleware()
        );
    }
}
