<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Services\SessionRevocationService;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    public function __construct(
        private readonly SessionRevocationService $sessions
    ) {}

    /**
     * Validate and update the user's password.
     *
     * @param array<string, string> $input
     */
    public function update(
        User $user,
        array $input
    ): void {
        Validator::make(
            $input,
            [
                'current_password' => [
                    'required',
                    'string',
                    'current_password:web',
                ],
                'password' =>
                    $this->passwordRules(),
            ],
            [
                'current_password.current_password' =>
                    __('The provided password does not match your current password.'),
            ]
        )->validateWithBag(
            'updatePassword'
        );

        $user->forceFill([
            'password' => $input['password'],
            'password_changed_at' => now(),
        ])->save();

        $this->sessions->revokeFor(
            $user,
            session()->getId()
        );
    }
}
