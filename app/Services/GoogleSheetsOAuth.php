<?php

namespace App\Services;

use App\Models\GoogleConnection;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GoogleSheetsOAuth
{
    public function client(): Client
    {
        $id = config('services.google_sheets.client_id');
        $secret = config('services.google_sheets.client_secret');
        $redirect = config('services.google_sheets.redirect');
        if (! $id || ! $secret || ! $redirect) {
            throw new RuntimeException('OAuth Google belum dikonfigurasi di .env.');
        }

        $client = new Client();
        $client->setClientId($id);
        $client->setClientSecret($secret);
        $client->setRedirectUri($redirect);
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        return $client;
    }

    public function authorizedClient(int $userId): Client
    {
        $connection = GoogleConnection::query()->where('user_id', $userId)->first();
        if (! $connection) {
            throw new RuntimeException('Hubungkan akun Google dahulu.');
        }

        $client = $this->client();
        $client->setAccessToken([
            'access_token' => $connection->access_token,
            'expires_in' => max(0, (int) ($connection->expires_at?->timestamp ?? 0) - time()),
            'created' => time(),
        ]);

        if (! $client->isAccessTokenExpired()) {
            return $client;
        }
        if (! $connection->refresh_token) {
            throw new RuntimeException('Sesi Google kedaluwarsa. Putuskan lalu hubungkan ulang akun Google.');
        }

        // Row lock prevents two simultaneous Livewire requests from rotating tokens at once.
        return DB::transaction(function () use ($userId) {
            $connection = GoogleConnection::query()->where('user_id', $userId)->lockForUpdate()->firstOrFail();
            $client = $this->client();
            if ($connection->expires_at && $connection->expires_at->isFuture()) {
                $client->setAccessToken(['access_token' => $connection->access_token, 'expires_in' => max(1, $connection->expires_at->timestamp - time()), 'created' => time()]);
                return $client;
            }
            $token = $client->fetchAccessTokenWithRefreshToken($connection->refresh_token);
            if (isset($token['error']) || empty($token['access_token'])) {
                throw new RuntimeException('Gagal memperbarui token Google. Hubungkan ulang akun Google.');
            }
            $connection->update([
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? $connection->refresh_token,
                'expires_at' => now()->addSeconds(max(60, (int) ($token['expires_in'] ?? 3600)) - 30),
            ]);
            $client->setAccessToken($token);
            return $client;
        });
    }
}
