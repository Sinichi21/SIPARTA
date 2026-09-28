<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterAttachment extends Model
{
    protected $fillable = [
        'letter_id',
        'original_name',
        'stored_name',
        'path',
        'mime_type',
        'size',
        'checksum_sha256',
        'uploaded_by',
    ];

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}