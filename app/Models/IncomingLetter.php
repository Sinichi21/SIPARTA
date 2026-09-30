<?php

namespace App\Models;

use App\Enums\IncomingLetterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomingLetter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agenda_number',
        'number',
        'letter_date',
        'received_date',
        'sender',
        'subject',
        'classification',
        'nature',
        'attachment_note',
        'destination',
        'status',
        'notes',
        'original_file_path',
        'original_file_name',
        'created_by',
        'updated_by',
        'disposed_at',
        'processed_at',
        'completed_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date',
            'received_date' => 'date',
            'disposed_at' => 'datetime',
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
            'status' => IncomingLetterStatus::class,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function canBeEdited(): bool
    {
        return $this->status === IncomingLetterStatus::Recorded;
    }
}
