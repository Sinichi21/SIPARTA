<?php

namespace App\Models;

use App\Enums\LetterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Letter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'letter_type_id',
        'activity_type_id',
        'number',
        'subject',
        'letter_date',
        'start_date',
        'end_date',
        'location',
        'basis',
        'description',
        'status',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
        'published_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',

            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'cancelled_at' => 'datetime',

            'status' => LetterStatus::class,
        ];
    }

    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class);
    }

    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function personnels(): BelongsToMany
    {
        return $this->belongsToMany(
            Personnel::class,
            'letter_personnel'
        )->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LetterAttachment::class);
    }
}