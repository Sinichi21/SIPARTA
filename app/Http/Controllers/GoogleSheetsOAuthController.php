<?php

namespace App\Http\Controllers;

use App\Models\GoogleConnection;
use App\Services\GoogleSheetsOAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Throwable;

class GoogleSheetsOAuthController extends Controller
{
    public function redirect(Request $request, GoogleSheetsOAuth $oauth): RedirectResponse
    {
        Gate::authorize('letters.import');
        $state = Str::random(64);
        $request->session()->put('google_sheets_oauth_state', $state);
        $client = $oauth->client();
        $client->setState($state);
        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request, GoogleSheetsOAuth $oauth): RedirectResponse
    {
        Gate::authorize('letters.import');
        $expected = $request->session()->pull('google_sheets_oauth_state');
        if (! is_string($expected) || ! hash_equals($expected, (string) $request->query('state', ''))) {
            return redirect()->route('spt-import.index')->with('google_error', 'OAuth state tidak valid. Ulangi koneksi.');
        }
        if ($request->query('error')) {
            return redirect()->route('spt-import.index')->with('google_error', 'Akses Google tidak diberikan.');
        }
        $code = $request->query('code');
        if (! is_string($code) || $code === '') {
            return redirect()->route('spt-import.index')->with('google_error', 'Kode OAuth tidak ditemukan.');
        }
        try {
            $token = $oauth->client()->fetchAccessTokenWithAuthCode($code);
            if (isset($token['error']) || empty($token['access_token'])) {
                throw new \RuntimeException('Google tidak memberikan access token.');
            }
            $existing = GoogleConnection::query()->where('user_id', $request->user()->id)->first();
            $refresh = $token['refresh_token'] ?? $existing?->refresh_token;
            if (! $refresh) {
                throw new \RuntimeException('Refresh token tidak diberikan. Cabut izin aplikasi di akun Google, lalu hubungkan ulang.');
            }
            GoogleConnection::query()->updateOrCreate(
                ['user_id' => $request->user()->id],
                ['access_token' => $token['access_token'], 'refresh_token' => $refresh,
                 'expires_at' => now()->addSeconds(max(60, (int) ($token['expires_in'] ?? 3600)) - 30)]
            );
            $request->session()->regenerate();
            return redirect()->route('spt-import.index')->with('google_success', 'Akun Google berhasil dihubungkan.');
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('spt-import.index')->with('google_error', 'Gagal menghubungkan akun Google. Periksa konfigurasi dan izin Google.');
        }
    }

    public function disconnect(Request $request): RedirectResponse
    {
        Gate::authorize('letters.import');
        // Deletes locally stored tokens. Google permissions can additionally be revoked in the Google account.
        GoogleConnection::query()->where('user_id', $request->user()->id)->delete();
        return redirect()->route('spt-import.index')->with('google_success', 'Koneksi lokal Google telah diputus.');
    }
}
