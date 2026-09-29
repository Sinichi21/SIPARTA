<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'original_filename',
        'stored_filename',
        'status',
        'total_rows',
        'valid_rows',
        'failed_rows',
        'imported_rows',
        'mapping',
        'summary',
        'uploaded_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'mapping' => 'array',
            'summary' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class);
    }
}
