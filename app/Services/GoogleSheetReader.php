<?php

namespace App\Services;

use Google\Service\Sheets;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleSheetReader
{
    public function __construct(private readonly GoogleSheetsOAuth $oauth) {}

    public function listSheetsApi(int $userId, string $url): array
    {
        $book = (new Sheets($this->oauth->authorizedClient($userId)))
            ->spreadsheets->get($this->spreadsheetId($url), ['fields' => 'sheets.properties']);
        return array_map(static fn ($sheet) => [
            'title' => $sheet->getProperties()->getTitle(),
            'gid' => $sheet->getProperties()->getSheetId(),
        ], $book->getSheets() ?? []);
    }

    public function readApi(int $userId, string $url, string $sheet, string $range): array
    {
        $this->validateRange($range);
        if (trim($sheet) === '') {
            throw new RuntimeException('Pilih tab spreadsheet.');
        }
        $sheet = str_replace("'", "''", $sheet);
        $values = (new Sheets($this->oauth->authorizedClient($userId)))
            ->spreadsheets_values->get($this->spreadsheetId($url), "'{$sheet}'!{$range}", [
                'valueRenderOption' => 'FORMATTED_VALUE',
            ])->getValues() ?? [];
        return $this->normalize($values);
    }

    public function readPublic(string $url, string $range): array
    {
        $this->validateRange($range);
        $id = $this->spreadsheetId($url);
        $gid = $this->gid($url);
        $endpoint = "https://docs.google.com/spreadsheets/d/{$id}/gviz/tq";
        $response = Http::timeout(20)->get($endpoint, [
            'tqx' => 'out:csv', 'gid' => $gid, 'range' => $range,
        ]);
        if (! $response->successful() || str_contains(strtolower($response->header('content-type', '')), 'html')) {
            throw new RuntimeException('Link tidak dapat dibaca. Pastikan spreadsheet publik dan URL memakai tab yang tepat.');
        }
        if (strlen($response->body()) > 5_000_000) {
            throw new RuntimeException('Respons CSV terlalu besar. Perkecil range.');
        }
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $response->body());
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream)) !== false) {
            if (! (count($row) === 1 && trim((string) ($row[0] ?? '')) === '')) {
                $rows[] = $row;
            }
            if (count($rows) > 5001) {
                fclose($stream);
                throw new RuntimeException('Maksimal 5.000 baris per impor.');
            }
        }
        fclose($stream);
        return $this->normalize($rows);
    }

    private function spreadsheetId(string $value): string
    {
        $value = trim($value);
        if (preg_match(
            '~^https://docs\.google\.com/spreadsheets/d/([A-Za-z0-9_-]+)(?:[/?#]|$)~',
            $value,
            $match
        )) {
            return $match[1];
        }
        // Permit only long ID-like strings, never arbitrary remote URLs.
        if (preg_match('/^[A-Za-z0-9_-]{20,}$/', $value)) {
            return $value;
        }
        throw new RuntimeException('Masukkan URL Google Sheets standar yang valid.');
    }

    private function gid(string $url): string
    {
        $parts = parse_url($url);
        parse_str($parts['fragment'] ?? '', $fragment);
        parse_str($parts['query'] ?? '', $query);
        $gid = (string) ($fragment['gid'] ?? $query['gid'] ?? '0');
        if (! ctype_digit($gid)) {
            throw new RuntimeException('gid pada URL tidak valid.');
        }
        return $gid;
    }

    private function validateRange(string $range): void
    {
        if (! preg_match('/^[A-Z]{1,3}[1-9]\d*:[A-Z]{1,3}[1-9]\d*$/i', trim($range))) {
            throw new RuntimeException('Range harus berbentuk A1:K100.');
        }
    }

    private function normalize(array $values): array
    {
        if (count($values) > 5001) {
            throw new RuntimeException('Maksimal 5.000 baris per impor.');
        }
        if ($values === []) {
            return ['headers' => [], 'rows' => []];
        }
        $first = array_values($values[0]);
        $headers = [];
        foreach ($first as $index => $item) {
            $base = trim((string) $item) ?: 'Kolom '.($index + 1);
            $header = $base;
            $i = 2;
            while (in_array($header, $headers, true)) {
                $header = $base.' ('.$i++.')';
            }
            $headers[] = $header;
        }
        $rows = [];
        foreach (array_slice($values, 1) as $row) {
            $assoc = [];
            foreach ($headers as $i => $header) {
                $assoc[$header] = trim((string) ($row[$i] ?? ''));
            }
            if (count(array_filter($assoc, static fn ($value) => $value !== '')) > 0) {
                $rows[] = $assoc;
            }
        }
        return ['headers' => $headers, 'rows' => $rows];
    }
}
