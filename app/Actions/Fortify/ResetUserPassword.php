<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Services\SessionRevocationService;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(
        private readonly SessionRevocationService $sessions
    ) {}

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param array<string, string> $input
     */
    public function reset(
        User $user,
        array $input
    ): void {
        Validator::make(
            $input,
            [
                'password' =>
                    $this->passwordRules(),
            ]
        )->validate();

        $user->forceFill([
            'password' => $input['password'],
            'password_changed_at' => now(),
            'must_set_password' => false,
            'email_verified_at' => $user->email_verified_at ?: now(),
        ])->save();

        $this->sessions->revokeFor($user);
    }
}
