<?php

namespace Tests\Feature;

use App\Models\User;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRolePhaseTwelveTest extends TestCase
{
    use RefreshDatabase;

    public function test_activation_password_reset_completes_account_activation(): void
    {
        $user = User::factory()->create();

        $user->forceFill([
            'must_set_password' => true,
            'email_verified_at' => null,
        ])->save();

        app(ResetUserPassword::class)->reset(
            $user,
            [
                'password' => 'StrongPassword!2026',
                'password_confirmation' => 'StrongPassword!2026',
            ]
        );

        $user->refresh();

        $this->assertFalse(
            (bool) $user->must_set_password
        );

        $this->assertNotNull(
            $user->email_verified_at
        );

        $this->assertTrue(
            Hash::check(
                'StrongPassword!2026',
                $user->password
            )
        );
    }

    public function test_user_model_can_link_to_personnel(): void
    {
        $this->assertTrue(
            method_exists(
                User::class,
                'personnel'
            )
        );
    }
}
