<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SptReportAttachment extends Model
{
    protected $fillable = [
        'spt_report_id',
        'original_name',
        'path',
        'mime_type',
        'size',
        'checksum_sha256',
        'uploaded_by',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(SptReport::class, 'spt_report_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
