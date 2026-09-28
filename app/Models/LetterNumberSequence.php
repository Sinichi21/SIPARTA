<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterNumberSequence extends Model
{
    protected $fillable = [
        'letter_type_id',
        'unit_id',
        'year',
        'last_number',
    ];

    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}