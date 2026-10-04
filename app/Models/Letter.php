<?php

namespace App\Models;

use App\Enums\LetterRecordType;
use App\Enums\LetterStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Letter extends Model
{
    use SoftDeletes;

    public const PERSONNEL_SCOPE_SELECTED = 'selected';
    public const PERSONNEL_SCOPE_TEAM = 'team';
    public const PERSONNEL_SCOPE_ALL = 'all';

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
        'assignment_purpose',
        'departure_place',
        'destination_place',
        'transport_mode',
        'budget_account',
        'description',
        'status',
        'source',
        'record_type',
        'personnel_scope',
        'personnel_team_id',
        'import_batch_id',
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
            'record_type' => LetterRecordType::class,
        ];
    }

    public function scopeSpt(Builder $query): Builder
    {
        return $query->whereHas(
            'letterType',
            fn (Builder $letterTypeQuery) => $letterTypeQuery->where('code', 'SPT')
        );
    }

    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class);
    }

    public function canBeEdited(): bool
    {
        return $this->status === LetterStatus::Draft || $this->source === 'import';
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

    public function assignsAllPersonnel(): bool
    {
        return $this->personnel_scope === self::PERSONNEL_SCOPE_ALL;
    }

    public function assignsPersonnelTeam(): bool
    {
        return $this->personnel_scope === self::PERSONNEL_SCOPE_TEAM;
    }

    public function personnelTeam(): BelongsTo
    {
        return $this->belongsTo(PersonnelTeam::class);
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

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LetterAttachment::class);
    }

    public function outgoingLetter(): HasOne
    {
        return $this->hasOne(
            OutgoingLetter::class,
            'source_spt_id'
        );
    }
    public function documentSnapshot(): HasOne
    {
        return $this->hasOne(
            LetterDocumentSnapshot::class
        );
    }}
