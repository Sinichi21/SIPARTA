<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SptSubmissionEvent extends Model
{
    protected $fillable = ['letter_id', 'actor_id', 'event', 'from_status', 'to_status', 'note'];
    public function letter(): BelongsTo { return $this->belongsTo(Letter::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
