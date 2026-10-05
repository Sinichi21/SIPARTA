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

    public function sptWorkflowEvent(
        Model $subject,
        string $action,
        string $fromStatus,
        string $toStatus,
        int $actorId,
        ?string $note = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $actorId,
            'action' => strtoupper($action),
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'old_values' => ['status' => $fromStatus],
            'new_values' => [
                'status' => $toStatus,
                'note' => $note,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public function merged(
        Model $subject,
        array $oldValues,
        array $metadata = []
    ): AuditLog {
        $newValues = $this->safeAttributes(
            $subject->getAttributes()
        );

        $newValues['_merge'] = $metadata;

        return $this->write(
            'MERGE',
            $subject,
            $this->safeAttributes($oldValues),
            $newValues
        );
    }

    public function attachmentUploaded(Model $subject): AuditLog
    {
        return $this->write(
            'UPLOAD',
            $subject,
            null,
            $this->safeAttributes(
                $subject->getAttributes()
            )
        );
    }

    public function attachmentDeleted(Model $subject): AuditLog
    {
        return $this->write(
            'DELETE',
            $subject,
            $this->safeAttributes(
                $subject->getAttributes()
            ),
            null
        );
    }

    public function removedImportedPersonnel(
        Model $subject,
        array $oldValues,
        int $detachedLetters
    ): AuditLog {
        return $this->write(
            'CLEANUP',
            $subject,
            $this->safeAttributes($oldValues),
            [
                'deleted_at' => now()->toISOString(),
                'detached_letter_relations' => $detachedLetters,
            ]
        );
    }

    public function documentGenerated(Model $subject): AuditLog
    {
        return $this->write(
            'GENERATE',
            $subject,
            null,
            $this->safeAttributes(
                $subject->getAttributes()
            )
        );
    }

    public function securityEvent(
        Model $subject,
        string $action,
        array $metadata = []
    ): AuditLog {
        return $this->write(
            strtoupper($action),
            $subject,
            null,
            [
                '_security' => $metadata,
            ]
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