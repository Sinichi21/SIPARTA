<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personnel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'unit_id',
        'nip',
        'name',
        'rank',
        'grade',
        'position',
        'email',
        'phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(
            PersonnelTeam::class,
            'personnel_team_members'
        )->withTimestamps();
    }

    public function letters(): BelongsToMany
    {
        return $this->belongsToMany(
            Letter::class,
            'letter_personnel'
        )->withTimestamps();
    }
}