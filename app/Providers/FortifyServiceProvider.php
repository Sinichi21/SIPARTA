<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureAuthentication();
        $this->configureLoginAudit();
    }

    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $email = Str::lower(
                trim((string) $request->input(Fortify::username()))
            );

            $user = User::query()
                ->where('email', $email)
                ->first();

            if (
                ! $user
                || $user->account_disabled_at
                || $user->must_set_password
                || ! Hash::check(
                    (string) $request->input('password'),
                    $user->password
                )
            ) {
                return null;
            }

            return $user;
        });
    }

    private function configureLoginAudit(): void
    {
        Event::listen(
            Login::class,
            function (Login $event): void {
                if (! $event->user instanceof User) {
                    return;
                }

                $event->user->forceFill([
                    'last_login_at' => now(),
                    'last_login_ip' => request()?->ip(),
                ])->saveQuietly();
            }
        );
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(
            ResetUserPassword::class
        );

        Fortify::updateUserPasswordsUsing(
            UpdateUserPassword::class
        );

        Fortify::createUsersUsing(
            CreateNewUser::class
        );
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(
            fn () => view('pages::auth.login')
        );

        Fortify::verifyEmailView(
            fn () => view('pages::auth.verify-email')
        );

        Fortify::twoFactorChallengeView(
            fn () => view(
                'pages::auth.two-factor-challenge'
            )
        );

        Fortify::confirmPasswordView(
            fn () => view(
                'pages::auth.confirm-password'
            )
        );

        Fortify::registerView(
            fn () => view('pages::auth.register')
        );

        Fortify::resetPasswordView(
            fn () => view(
                'pages::auth.reset-password'
            )
        );

        Fortify::requestPasswordResetLinkView(
            fn () => view(
                'pages::auth.forgot-password'
            )
        );
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for(
            'two-factor',
            function (Request $request) {
                return Limit::perMinute(5)
                    ->by(
                        $request
                            ->session()
                            ->get('login.id')
                    );
            }
        );

        RateLimiter::for(
            'login',
            function (Request $request) {
                $throttleKey =
                    Str::transliterate(
                        Str::lower(
                            $request->input(
                                Fortify::username()
                            )
                        )
                        .'|'
                        .$request->ip()
                    );

                return Limit::perMinute(5)
                    ->by($throttleKey);
            }
        );

        RateLimiter::for(
            'passkeys',
            function (Request $request) {
                $credentialId =
                    $request->input(
                        'credential.id'
                    );

                return Limit::perMinute(10)
                    ->by(
                        (
                            $credentialId
                            ?: $request
                                ->session()
                                ->getId()
                        )
                        .'|'
                        .$request->ip()
                    );
            }
        );
    }
}
