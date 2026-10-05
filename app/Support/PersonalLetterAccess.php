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

        return $query->where(function (Builder $query) use ($user) {
            $query->where('personnel_scope', Letter::PERSONNEL_SCOPE_ALL)
                ->orWhereHas(
                    'personnels',
                    fn (Builder $personnelQuery) =>
                        $personnelQuery->where(
                            'personnels.id',
                            $user->personnel_id
                        )
                );
        });
    }

    public function canAccess(
        User $user,
        Letter $letter
    ): bool {
        if (! $user->personnel_id) {
            return false;
        }

        if ($letter->assignsAllPersonnel()) {
            return true;
        }

        return $letter->personnels()
            ->where(
                'personnels.id',
                $user->personnel_id
            )
            ->exists();
    }
}
