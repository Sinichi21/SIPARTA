<?php

namespace App\Services;

use App\Models\LetterType;
use App\Models\NumberingPlaceholder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class NumberingPlaceholderService
{
    public const BUILTIN = [
        'sequence',
        'sequence_padded',
        'type',
        'month',
        'month_roman',
        'year',
        'year_short',
    ];

    public function forPattern(?string $pattern): Collection
    {
        if (blank($pattern)) {
            return collect();
        }

        preg_match_all('/\{([a-z][a-z0-9_]*)\}/i', $pattern, $matches);

        $keys = collect($matches[1] ?? [])
            ->map(fn ($key) => strtolower($key))
            ->reject(fn ($key) => in_array($key, self::BUILTIN, true))
            ->unique()
            ->values();

        if ($keys->isEmpty()) {
            return collect();
        }

        return NumberingPlaceholder::query()
            ->whereIn('key', $keys)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn (NumberingPlaceholder $placeholder) => $keys->search($placeholder->key))
            ->values();
    }

    public function validateForType(?LetterType $type, array $values): array
    {
        $definitions = $this->forPattern($type?->numbering_pattern);
        $clean = [];

        foreach ($definitions as $definition) {
            $value = trim((string) ($values[$definition->key] ?? ''));

            if ($definition->is_required && $value === '') {
                throw ValidationException::withMessages([
                    'manualFields.'.$definition->key => $definition->label.' wajib diisi untuk nomor surat.',
                ]);
            }

            if ($value === '') {
                continue;
            }

            if (
                $definition->type === 'select'
                && ! in_array($value, $definition->normalizedOptions(), true)
            ) {
                throw ValidationException::withMessages([
                    'manualFields.'.$definition->key => 'Pilihan '.$definition->label.' tidak valid.',
                ]);
            }

            $clean[$definition->key] = $value;
        }

        return $clean;
    }
}
