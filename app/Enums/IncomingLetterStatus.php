<?php

namespace App\Enums;

enum IncomingLetterStatus: string
{
    case Recorded = 'recorded';
    case Disposed = 'disposed';
    case Processing = 'processing';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Recorded => 'Dicatat',
            self::Disposed => 'Didisposisikan',
            self::Processing => 'Diproses',
            self::Completed => 'Selesai',
            self::Archived => 'Diarsipkan',
        };
    }
}
