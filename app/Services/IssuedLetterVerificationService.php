<?php

namespace App\Services;

use App\Models\IssuedLetter;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class IssuedLetterVerificationService
{
    public function ensureCode(IssuedLetter $issued): IssuedLetter
    {
        if ($issued->verification_code) {
            return $issued;
        }

        do {
            $code = bin2hex(random_bytes(24));
        } while (
            IssuedLetter::query()
                ->where('verification_code', $code)
                ->exists()
        );

        $issued->forceFill([
            'verification_code' => $code,
        ])->save();

        return $issued->fresh();
    }

    public function publicUrl(IssuedLetter $issued): string
    {
        $issued = $this->ensureCode($issued);

        return route('issued-letters.verify', [
            'code' => $issued->verification_code,
        ]);
    }

    public function qrDataUri(IssuedLetter $issued): string
    {
        $writer = extension_loaded('gd')
            ? new PngWriter()
            : new SvgWriter();

        return Builder::create()
            ->writer($writer)
            ->writerOptions([])
            ->validateResult(false)
            ->data($this->publicUrl($issued))
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Medium)
            ->size(220)
            ->margin(8)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build()
            ->getDataUri();
    }

    public function findPublic(string $code): IssuedLetter
    {
        abort_unless(
            preg_match('/^[a-f0-9]{48}$/', $code) === 1,
            404
        );

        $issued = IssuedLetter::query()
            ->with([
                'letterType',
                'issuer',
                'outgoingLetter.letterheadProfile',
            ])
            ->where('verification_code', $code)
            ->firstOrFail();

        $issued->increment('verification_count');

        $issued->forceFill([
            'last_verified_at' => now(),
        ])->save();

        return $issued->fresh([
            'letterType',
            'issuer',
            'outgoingLetter.letterheadProfile',
        ]);
    }

    public function archivedPdfBinary(IssuedLetter $issued): string
    {
        if (! $issued->hasArchivedPdf()) {
            throw ValidationException::withMessages([
                'document' => 'Arsip PDF resmi belum tersedia.',
            ]);
        }

        abort_unless(
            Storage::exists($issued->pdf_path),
            404,
            'Arsip PDF resmi tidak ditemukan.'
        );

        $binary = Storage::get($issued->pdf_path);

        if ($issued->file_sha256) {
            $actual = hash('sha256', $binary);

            if (! hash_equals(
                (string) $issued->file_sha256,
                $actual
            )) {
                throw ValidationException::withMessages([
                    'document' => 'Integritas arsip PDF tidak valid. Hash file tidak sesuai catatan penerbitan.',
                ]);
            }
        }

        return $binary;
    }

    public function assertQrPackageAvailable(): void
    {
        if (! class_exists(Builder::class)) {
            throw new RuntimeException(
                'Package endroid/qr-code belum terpasang.'
            );
        }
    }
}
