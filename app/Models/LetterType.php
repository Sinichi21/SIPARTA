<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LetterType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'numbering_pattern',
        'requires_personnel',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_personnel' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(LetterTemplate::class);
    }}