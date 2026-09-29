<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class TabularFileReader
{
    public function read(string $path, string $extension): array
    {
        return match (strtolower($extension)) {
            'csv' => $this->readCsv($path),
            'xlsx' => $this->readXlsx($path),
            default => throw new RuntimeException('Format file belum didukung. Gunakan CSV atau XLSX.'),
        };
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');

        if (! $handle) {
            throw new RuntimeException('File CSV tidak dapat dibaca.');
        }

        $firstLine = fgets($handle) ?: '';
        rewind($handle);

        $delimiters = [',', ';', "\t", '|'];
        $delimiter = ',';
        $bestCount = 0;

        foreach ($delimiters as $candidate) {
            $count = count(str_getcsv($firstLine, $candidate));

            if ($count > $bestCount) {
                $bestCount = $count;
                $delimiter = $candidate;
            }
        }

        $rows = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }

            $rows[] = array_map(
                fn ($value) => is_string($value) ? trim($value) : $value,
                $row
            );
        }

        fclose($handle);

        return $this->normalizeRows($rows);
    }

    private function readXlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP ZipArchive belum aktif sehingga file XLSX belum dapat dibaca.');
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('File XLSX tidak dapat dibuka.');
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedXml !== false) {
            $xml = simplexml_load_string($sharedXml);

            if ($xml) {
                foreach ($xml->si as $si) {
                    $text = '';

                    if (isset($si->t)) {
                        $text = (string) $si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                    }

                    $sharedStrings[] = $text;
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

        if ($sheetXml === false) {
            $zip->close();
            throw new RuntimeException('Worksheet pertama tidak ditemukan pada file XLSX.');
        }

        $xml = simplexml_load_string($sheetXml);
        $zip->close();

        if (! $xml) {
            throw new RuntimeException('Isi XLSX tidak dapat dibaca.');
        }

        $rows = [];

        foreach ($xml->sheetData->row as $rowNode) {
            $row = [];
            $maxColumn = -1;

            foreach ($rowNode->c as $cell) {
                $reference = (string) $cell['r'];
                $columnLetters = preg_replace('/\d+/', '', $reference);
                $columnIndex = $this->columnIndex($columnLetters);
                $maxColumn = max($maxColumn, $columnIndex);

                $type = (string) $cell['t'];
                $value = '';

                if ($type === 'inlineStr') {
                    $value = (string) $cell->is->t;
                } else {
                    $raw = (string) $cell->v;
                    $value = $type === 's'
                        ? ($sharedStrings[(int) $raw] ?? '')
                        : $raw;
                }

                $row[$columnIndex] = trim((string) $value);
            }

            if ($maxColumn < 0) {
                continue;
            }

            $normalized = [];

            for ($i = 0; $i <= $maxColumn; $i++) {
                $normalized[] = $row[$i] ?? '';
            }

            if (collect($normalized)->filter(fn ($v) => trim((string) $v) !== '')->isNotEmpty()) {
                $rows[] = $normalized;
            }
        }

        return $this->normalizeRows($rows);
    }

    private function columnIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function normalizeRows(array $rows): array
    {
        if ($rows === []) {
            return [
                'headers' => [],
                'rows' => [],
            ];
        }

        $headers = array_map(
            fn ($value, $index) => filled($value) ? trim((string) $value) : 'Kolom '.($index + 1),
            $rows[0],
            array_keys($rows[0])
        );

        $dataRows = [];

        foreach (array_slice($rows, 1) as $row) {
            $assoc = [];

            foreach ($headers as $index => $header) {
                $assoc[$header] = $row[$index] ?? '';
            }

            if (collect($assoc)->filter(fn ($v) => trim((string) $v) !== '')->isNotEmpty()) {
                $dataRows[] = $assoc;
            }
        }

        return [
            'headers' => $headers,
            'rows' => $dataRows,
        ];
    }
}
