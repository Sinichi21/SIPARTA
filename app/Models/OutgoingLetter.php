<?php

namespace App\Models;

use App\Enums\OutgoingLetterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class OutgoingLetter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'letter_type_id',
        'letter_template_id',
        'letterhead_profile_id',
        'number',
        'letter_date',
        'recipient',
        'subject',
        'classification',
        'nature',
        'content_html',
        'status',
        'notes',
        'created_by',
        'updated_by',
        'verified_by',
        'verified_at',
        'approved_by',
        'approved_at',
        'numbered_by',
        'numbered_at',
        'published_by',
        'published_at',
        'sent_by',
        'sent_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'numbered_at' => 'datetime',
            'published_at' => 'datetime',
            'sent_at' => 'datetime',
            'archived_at' => 'datetime',
            'status' => OutgoingLetterStatus::class,
        ];
    }

    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LetterTemplate::class, 'letter_template_id');
    }

    public function letterheadProfile(): BelongsTo
    {
        return $this->belongsTo(LetterheadProfile::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function issuedLetter(): HasOne
    {
        return $this->hasOne(IssuedLetter::class);
    }

    public function canBeEdited(): bool
    {
        return $this->status === OutgoingLetterStatus::Draft;
    }
}
