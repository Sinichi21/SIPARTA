<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReadinessController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $ready = true;

        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $ready = false;
        }

        try {
            $probe = 'health/readiness-probe.txt';
            Storage::disk('local')->put($probe, 'ok');
            Storage::disk('local')->delete($probe);
        } catch (\Throwable) {
            $ready = false;
        }

        return response()->json(
            ['status' => $ready ? 'ready' : 'unavailable'],
            $ready ? 200 : 503
        );
    }
}
