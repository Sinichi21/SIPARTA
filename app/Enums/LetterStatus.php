<?php

namespace App\Enums;

enum LetterStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Approved = 'approved';
    case Published = 'published';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Diajukan',
            self::Verified => 'Diverifikasi',
            self::Approved => 'Disetujui',
            self::Published => 'Diterbitkan',
            self::Cancelled => 'Dibatalkan',
            self::Archived => 'Diarsipkan',
        };
    }
}