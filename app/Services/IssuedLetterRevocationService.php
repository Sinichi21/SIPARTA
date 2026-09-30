<?php

namespace App\Services;

use App\Models\IssuedLetter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssuedLetterRevocationService
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function revoke(
        IssuedLetter $issued,
        User $actor,
        string $reason
    ): IssuedLetter {
        $reason = trim($reason);

        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages([
                'revocationReason' => 'Alasan pencabutan minimal 10 karakter.',
            ]);
        }

        return DB::transaction(function () use ($issued, $actor, $reason) {
            $locked = IssuedLetter::query()
                ->whereKey($issued->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->isRevoked()) {
                return $locked;
            }

            $old = $locked->getOriginal();

            $locked->forceFill([
                'status' => 'revoked',
                'revoked_at' => now(),
                'revoked_by' => $actor->id,
                'revocation_reason' => $reason,
            ])->save();

            $this->audit->cancelled($locked, $old);

            return $locked->fresh([
                'letterType',
                'issuer',
                'revoker',
                'outgoingLetter.letterheadProfile',
            ]);
        }, 3);
    }
}
