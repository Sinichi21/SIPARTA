<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterDocumentSnapshot extends Model
{
    protected $fillable = [
        'letter_id','letter_template_id','template_name','template_code','template_version',
        'rendered_html','letter_snapshot','letterhead_snapshot','checksum_sha256',
        'generated_by','generated_at',
    ];

    protected function casts(): array
    {
        return [
            'template_version' => 'integer',
            'letter_snapshot' => 'array',
            'letterhead_snapshot' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function letter(): BelongsTo { return $this->belongsTo(Letter::class); }
    public function template(): BelongsTo { return $this->belongsTo(LetterTemplate::class, 'letter_template_id'); }
    public function generator(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }
}
