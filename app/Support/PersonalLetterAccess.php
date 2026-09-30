<?php

namespace App\Support;

use App\Models\Letter;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PersonalLetterAccess
{
    public function apply(
        Builder $query,
        User $user
    ): Builder {
        if (! $user->personnel_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'personnels',
            fn (Builder $personnelQuery) =>
                $personnelQuery->where(
                    'personnels.id',
                    $user->personnel_id
                )
        );
    }

    public function canAccess(
        User $user,
        Letter $letter
    ): bool {
        if (! $user->personnel_id) {
            return false;
        }

        return $letter->personnels()
            ->where(
                'personnels.id',
                $user->personnel_id
            )
            ->exists();
    }
}
