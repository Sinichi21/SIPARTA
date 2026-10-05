<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeploymentPreflight extends Command
{
    protected $signature = 'app:preflight {--strict : Enforce staging/production safety requirements}';

    protected $description = 'Validate application readiness before activating a deployment';

    public function handle(): int
    {
        $errors = [];
        $warnings = [];
        $strict = (bool) $this->option('strict')
            || app()->environment(['staging', 'production']);

        if (blank(config('app.key'))) {
            $errors[] = 'APP_KEY is not configured.';
        }

        if ($strict) {
            if ((bool) config('app.debug')) {
                $errors[] = 'APP_DEBUG must be false for staging/production.';
            }

            if (! str_starts_with((string) config('app.url'), 'https://')) {
                $errors[] = 'APP_URL must use HTTPS for staging/production.';
            }

            if (config('database.default') !== 'pgsql') {
                $errors[] = 'DB_CONNECTION must be pgsql for the target deployment.';
            }

            if (config('session.secure') !== true) {
                $errors[] = 'SESSION_SECURE_COOKIE must be true for staging/production.';
            }

            if (! in_array(config('session.driver'), ['database', 'redis'], true)) {
                $errors[] = 'SESSION_DRIVER must use database or redis for staging/production.';
            }

            if (! in_array(config('queue.default'), ['database', 'redis'], true)) {
                $errors[] = 'QUEUE_CONNECTION must use database or redis for staging/production.';
            }

            if (config('logging.default') === 'stack'
                && str_contains((string) env('LOG_STACK', 'single'), 'single')) {
                $warnings[] = 'Consider LOG_STACK=daily on staging/production.';
            }
        }

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $exception) {
            $errors[] = 'Database connection failed: '.$exception->getMessage();
        }

        try {
            $probe = '.deployment-preflight-'.getmypid().'.txt';

            if (! Storage::disk('local')->put($probe, 'ok')) {
                $errors[] = 'Local storage is not writable.';
            }

            Storage::disk('local')->delete($probe);
        } catch (\Throwable $exception) {
            $errors[] = 'Local storage check failed: '.$exception->getMessage();
        }

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        if ($errors !== []) {
            $this->error('Deployment preflight failed.');

            foreach ($errors as $error) {
                $this->line(' - '.$error);
            }

            return self::FAILURE;
        }

        $this->info('Deployment preflight passed.');

        return self::SUCCESS;
    }
}
