<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountSecurityService;
use App\Services\SessionRevocationService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountSecurityPhaseElevenTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_reset_sends_link_without_setting_password(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($admin);

        $before = $user->password;

        app(AccountSecurityService::class)
            ->sendPasswordReset($user);

        $user->refresh();

        $this->assertSame(
            $before,
            $user->password
        );

        Notification::assertSentTo(
            $user,
            ResetPassword::class
        );
    }

    public function test_reset_two_factor_clears_secret_and_recovery_codes(): void
    {
        $admin = User::factory()->create();

        $user = User::factory()->create();

        $user->forceFill([
            'two_factor_secret' =>
                encrypt('secret'),
            'two_factor_recovery_codes' =>
                encrypt(json_encode(['one'])),
            'two_factor_confirmed_at' =>
                now(),
        ])->save();

        $this->actingAs($admin);

        app(AccountSecurityService::class)
            ->resetTwoFactor($user);

        $user->refresh();

        $this->assertNull(
            $user->two_factor_secret
        );

        $this->assertNull(
            $user->two_factor_recovery_codes
        );

        $this->assertNull(
            $user->two_factor_confirmed_at
        );
    }

    public function test_session_revocation_removes_database_sessions(): void
    {
        $user = User::factory()->create();

        DB::table('sessions')->insert([
            'id' => 'phase11-test-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test',
            'last_activity' => time(),
        ]);

        $deleted =
            app(SessionRevocationService::class)
                ->revokeFor($user);

        $this->assertSame(1, $deleted);

        $this->assertDatabaseMissing(
            'sessions',
            [
                'id' =>
                    'phase11-test-session',
            ]
        );
    }

    public function test_disabled_account_state_is_recorded(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($admin);

        app(AccountSecurityService::class)
            ->disable(
                $user,
                $admin->id,
                'Akun dinonaktifkan untuk pengujian keamanan.'
            );

        $user->refresh();

        $this->assertNotNull(
            $user->account_disabled_at
        );

        $this->assertSame(
            $admin->id,
            $user->account_disabled_by
        );
    }
}
