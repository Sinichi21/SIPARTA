<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AccountSecurityService
{
    public function __construct(
        private readonly SessionRevocationService $sessions,
        private readonly AuditService $audit,
    ) {}

    public function sendPasswordReset(
        User $user
    ): string {
        $status = Password::sendResetLink([
            'email' => $user->email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'security' => __($status),
            ]);
        }

        $revoked =
            $this->sessions->revokeFor($user);

        $this->audit->securityEvent(
            $user,
            'PASSWORD_RESET_REQUEST',
            [
                'sessions_revoked' => $revoked,
            ]
        );

        return $status;
    }

    public function resetTwoFactor(
        User $user
    ): void {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $revoked =
            $this->sessions->revokeFor($user);

        $this->audit->securityEvent(
            $user,
            'TWO_FACTOR_RESET',
            [
                'sessions_revoked' => $revoked,
            ]
        );
    }

    public function revokeSessions(
        User $user
    ): int {
        $revoked =
            $this->sessions->revokeFor($user);

        $this->audit->securityEvent(
            $user,
            'SESSION_REVOKE',
            [
                'sessions_revoked' => $revoked,
            ]
        );

        return $revoked;
    }

    public function disable(
        User $user,
        int $actorId,
        string $reason
    ): void {
        if ($user->getKey() === $actorId) {
            throw ValidationException::withMessages([
                'security' =>
                    'Akun sendiri tidak dapat dinonaktifkan dari panel keamanan.',
            ]);
        }

        $reason = trim($reason);

        if (
            mb_strlen($reason) < 5
            || mb_strlen($reason) > 1000
        ) {
            throw ValidationException::withMessages([
                'disableReason' =>
                    'Alasan penonaktifan harus 5 sampai 1000 karakter.',
            ]);
        }

        $user->forceFill([
            'account_disabled_at' => now(),
            'account_disabled_by' => $actorId,
            'account_disabled_reason' => $reason,
        ])->save();

        $revoked =
            $this->sessions->revokeFor($user);

        $this->audit->securityEvent(
            $user,
            'ACCOUNT_DISABLE',
            [
                'reason' => $reason,
                'sessions_revoked' => $revoked,
            ]
        );
    }

    public function enable(
        User $user
    ): void {
        $user->forceFill([
            'account_disabled_at' => null,
            'account_disabled_by' => null,
            'account_disabled_reason' => null,
        ])->save();

        $this->audit->securityEvent(
            $user,
            'ACCOUNT_ENABLE'
        );
    }
}
