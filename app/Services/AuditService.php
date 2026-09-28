<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function created(Model $subject): AuditLog
    {
        return $this->write(
            'CREATE',
            $subject,
            null,
            $this->safeAttributes(
                $subject->getAttributes()
            )
        );
    }

    public function updated(
        Model $subject,
        array $oldValues
    ): AuditLog {
        return $this->changed(
            'UPDATE',
            $subject,
            $oldValues
        );
    }

    public function published(
        Model $subject,
        array $oldValues
    ): AuditLog {
        return $this->changed(
            'PUBLISH',
            $subject,
            $oldValues
        );
    }

    public function cancelled(
        Model $subject,
        array $oldValues
    ): AuditLog {
        return $this->changed(
            'CANCEL',
            $subject,
            $oldValues
        );
    }

    private function changed(
        string $action,
        Model $subject,
        array $oldValues
    ): AuditLog {
        $changes = $subject->getChanges();

        $new = $this->safeAttributes($changes);

        $old = collect($oldValues)
            ->only(array_keys($new))
            ->all();

        return $this->write(
            $action,
            $subject,
            $old,
            $new
        );
    }

    private function write(
        string $action,
        Model $subject,
        ?array $oldValues,
        ?array $newValues
    ): AuditLog {
        return AuditLog::create([
            'user_id' => auth()->id(),

            'action' => strtoupper($action),

            'subject_type' => $subject::class,

            'subject_id' => $subject->getKey(),

            'old_values' => $oldValues,

            'new_values' => $newValues,

            'ip_address' => request()?->ip(),

            'user_agent' => request()?->userAgent(),
        ]);
    }

    private function safeAttributes(
        array $attributes
    ): array {
        return collect($attributes)
            ->except([
                'password',
                'remember_token',
                'two_factor_secret',
                'two_factor_recovery_codes',
            ])
            ->all();
    }
}