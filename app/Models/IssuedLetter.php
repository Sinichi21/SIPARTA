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
        'snapshot_json',
        'checksum_sha256',
        'verification_code',
        'pdf_path',
        'pdf_name',
        'file_sha256',
        'file_size',
        'archived_document_at',
        'verification_count',
        'last_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date',
            'issued_at' => 'datetime',
            'snapshot_json' => 'array',
            'archived_document_at' => 'datetime',
            'verification_count' => 'integer',
            'last_verified_at' => 'datetime',
            'file_size' => 'integer',
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

    public function hasArchivedPdf(): bool
    {
        return filled($this->pdf_path);
    }
}
