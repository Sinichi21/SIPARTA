<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductionCheck extends Command
{
    protected $signature = 'app:production-check';
    protected $description = 'Check critical SIPARTA production configuration.';

    public function handle(): int
    {
        $failures = [];
        $warnings = [];

        if (! app()->environment('production')) {
            $failures[] = 'APP_ENV harus production.';
        }

        if (config('app.debug')) {
            $failures[] = 'APP_DEBUG harus false.';
        }

        if (blank(config('app.key'))) {
            $failures[] = 'APP_KEY belum tersedia.';
        }

        if (! Str::startsWith((string) config('app.url'), 'https://')) {
            $warnings[] = 'APP_URL belum menggunakan HTTPS.';
        }

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $failures[] = 'Database tidak dapat diakses: '.$e->getMessage();
        }

        try {
            $probe = 'health/production-check.txt';
            Storage::disk('local')->put($probe, now()->toISOString());
            Storage::disk('local')->delete($probe);
        } catch (\Throwable $e) {
            $failures[] = 'Private storage tidak writable: '.$e->getMessage();
        }

        if (! file_exists(public_path('storage'))) {
            $warnings[] = 'public/storage belum tersedia. Jalankan php artisan storage:link.';
        }

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        foreach ($failures as $failure) {
            $this->error($failure);
        }

        if ($failures !== []) {
            return self::FAILURE;
        }

        $this->info('Production check lulus.');

        return self::SUCCESS;
    }
}
