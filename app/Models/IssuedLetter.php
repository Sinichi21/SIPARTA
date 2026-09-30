<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssuedLetter extends Model
{
    protected $fillable = [
        'outgoing_letter_id',
        'letter_type_id',
        'number',
        'letter_date',
        'subject',
        'recipient',
        'signatory_name',
        'signatory_nip',
        'signatory_position',
        'issued_at',
        'issued_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date',
            'issued_at' => 'datetime',
        ];
    }

    public function outgoingLetter(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetter::class);
    }

    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
