<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SessionRevocationService
{
    public function revokeFor(
        User $user,
        ?string $exceptSessionId = null
    ): int {
        if (! Schema::hasTable('sessions')) {
            return 0;
        }

        $query = DB::table('sessions')
            ->where('user_id', $user->getKey());

        if (filled($exceptSessionId)) {
            $query->where('id', '!=', $exceptSessionId);
        }

        return $query->delete();
    }
}
