<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumberingPlaceholder extends Model
{
    protected $fillable = [
        'key',
        'label',
        'type',
        'options',
        'is_required',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function normalizedOptions(): array
    {
        return collect($this->options ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();
    }
}
