<?php

namespace App\Enums;

enum OutgoingLetterStatus: string
{
    case Draft = 'draft';
    case Verified = 'verified';
    case Approved = 'approved';
    case Numbered = 'numbered';
    case Published = 'published';
    case Sent = 'sent';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Verified => 'Diverifikasi',
            self::Approved => 'Disetujui',
            self::Numbered => 'Bernomor',
            self::Published => 'Diterbitkan',
            self::Sent => 'Dikirim',
            self::Archived => 'Diarsipkan',
        };
    }
}
