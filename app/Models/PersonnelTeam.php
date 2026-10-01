<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonnelTeam extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function personnels(): BelongsToMany
    {
        return $this->belongsToMany(
            Personnel::class,
            'personnel_team_members'
        )->withTimestamps();
    }

    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class);
    }
}
