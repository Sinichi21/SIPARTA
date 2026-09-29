<?php

namespace App\Enums;

enum LetterRecordType: string
{
    case Normal = 'normal';
    case AttendanceCorrection = 'attendance_correction';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'SPT Normal',
            self::AttendanceCorrection => 'Koreksi Absensi',
        };
    }
}
