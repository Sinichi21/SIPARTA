<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (
            ! $user
            || ! $user->account_disabled_at
        ) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if (
            $request->expectsJson()
            || $request->is('api/*')
        ) {
            return response()->json(
                [
                    'message' =>
                        'Akun dinonaktifkan.',
                ],
                403
            );
        }

        return redirect()
            ->route('login')
            ->withErrors([
                'email' =>
                    'Akun ini sedang dinonaktifkan. Hubungi administrator.',
            ]);
    }
}
