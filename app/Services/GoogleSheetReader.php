<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleSheetReader
{
    /**
     * Membaca spreadsheet private melalui Google Sheets API.
     */
    public function readApi(
        string $spreadsheetUrl,
        string $sheetName,
        string $range
    ): array {
        $spreadsheetId = $this->extractSpreadsheetId($spreadsheetUrl);

        $sheetName = trim($sheetName);
        $range = trim($range);

        if ($sheetName === '') {
            throw new RuntimeException('Sheet belum dipilih.');
        }

        if ($range === '') {
            throw new RuntimeException('Range belum diisi.');
        }

        $service = $this->makeSheetsService();

        $a1Notation = $this->buildA1Range(
            $sheetName,
            $range
        );

        try {
            $response = $service->spreadsheets_values->get(
                $spreadsheetId,
                $a1Notation,
                [
                    'majorDimension' => 'ROWS',
                    'valueRenderOption' => 'FORMATTED_VALUE',
                    'dateTimeRenderOption' => 'FORMATTED_STRING',
                ]
            );
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Gagal membaca Google Sheets API: '.$e->getMessage(),
                previous: $e
            );
        }

        $values = $response->getValues() ?? [];

        return $this->normalizeRows($values);
    }

    /**
     * Mengambil daftar tab/sheet dari spreadsheet private.
     */
    public function listSheetsApi(string $spreadsheetUrl): array
    {
        $spreadsheetId = $this->extractSpreadsheetId($spreadsheetUrl);

        $service = $this->makeSheetsService();

        try {
            $spreadsheet = $service->spreadsheets->get(
                $spreadsheetId,
                [
                    'fields' => 'sheets.properties',
                ]
            );
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Gagal membaca daftar sheet: '.$e->getMessage(),
                previous: $e
            );
        }

        $sheets = [];

        foreach ($spreadsheet->getSheets() ?? [] as $sheet) {
            $properties = $sheet->getProperties();

            $sheets[] = [
                'id' => $properties->getSheetId(),
                'title' => $properties->getTitle(),
                'row_count' => $properties->getGridProperties()?->getRowCount(),
                'column_count' => $properties->getGridProperties()?->getColumnCount(),
            ];
        }

        return $sheets;
    }

    /**
     * Membaca Google Sheets yang dapat diakses publik.
     *
     * Mendukung:
     * - URL spreadsheet biasa yang di-share "Anyone with the link"
     * - URL Publish to web /d/e/2PACX...
     */
    public function readPublic(
        string $spreadsheetUrl,
        string $range
    ): array {
        $spreadsheetUrl = trim($spreadsheetUrl);
        $range = trim($range);

        if ($spreadsheetUrl === '') {
            throw new RuntimeException('URL Google Sheets belum diisi.');
        }

        if ($range === '') {
            throw new RuntimeException('Range belum diisi.');
        }

        if ($this->isPublishedUrl($spreadsheetUrl)) {
            $url = $this->buildPublishedCsvUrl(
                $spreadsheetUrl,
                $range
            );
        } else {
            $url = $this->buildPublicCsvUrl(
                $spreadsheetUrl,
                $range
            );
        }

        try {
            $response = Http::timeout(30)
                ->retry(2, 500)
                ->withHeaders([
                    'User-Agent' => 'SIPARTA/1.0',
                    'Accept' => 'text/csv,text/plain,*/*',
                ])
                ->get($url);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Tidak dapat menghubungi Google Sheets.',
                previous: $e
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                sprintf(
                    'Google Sheets mengembalikan HTTP %d. Pastikan spreadsheet dapat diakses publik.',
                    $response->status()
                )
            );
        }

        $contentType = strtolower(
            $response->header('Content-Type', '')
        );

        $body = $response->body();

        /*
         * Kadang Google mengarahkan ke halaman login HTML
         * apabila spreadsheet ternyata tidak public.
         */
        if (
            str_contains($contentType, 'text/html')
            || str_contains(strtolower($body), '<!doctype html')
        ) {
            throw new RuntimeException(
                'Spreadsheet tidak dapat dibaca secara publik. '
                .'Pastikan akses Google Sheets sudah "Anyone with the link can view", '
                .'atau gunakan metode Google Sheets API.'
            );
        }

        return $this->parseCsv($body);
    }

    /**
     * Membuat Google Sheets service menggunakan service account.
     */
    private function makeSheetsService(): Sheets
    {
        $credentialsPath = config(
            'services.google_sheets.credentials_path'
        );

        if (! is_string($credentialsPath) || $credentialsPath === '') {
            throw new RuntimeException(
                'GOOGLE_SHEETS_CREDENTIALS belum dikonfigurasi.'
            );
        }

        if (! file_exists($credentialsPath)) {
            throw new RuntimeException(
                "Credential Google tidak ditemukan: {$credentialsPath}"
            );
        }

        $client = new Client;

        $client->setApplicationName('SIPARTA');
        $client->setAuthConfig($credentialsPath);
        $client->setScopes([
            Sheets::SPREADSHEETS_READONLY,
        ]);

        return new Sheets($client);
    }

    /**
     * Spreadsheet ID dari URL:
     *
     * https://docs.google.com/spreadsheets/d/ABC123/edit
     */
    public function extractSpreadsheetId(string $urlOrId): string
    {
        $value = trim($urlOrId);

        if ($value === '') {
            throw new RuntimeException(
                'URL atau ID spreadsheet kosong.'
            );
        }

        if (
            preg_match(
                '#/spreadsheets/d/([a-zA-Z0-9_-]+)#',
                $value,
                $matches
            )
        ) {
            return $matches[1];
        }

        /*
         * Izinkan Spreadsheet ID langsung.
         */
        if (
            ! str_contains($value, '/')
            && preg_match('/^[a-zA-Z0-9_-]+$/', $value)
        ) {
            return $value;
        }

        throw new RuntimeException(
            'URL Google Sheets tidak valid.'
        );
    }

    /**
     * Mengambil gid dari URL.
     */
    private function extractGid(string $url): string
    {
        $parts = parse_url($url);

        /*
         * gid biasanya berada setelah fragment:
         * #gid=12345
         */
        if (! empty($parts['fragment'])) {
            parse_str($parts['fragment'], $fragment);

            if (isset($fragment['gid'])) {
                return (string) $fragment['gid'];
            }
        }

        /*
         * Beberapa link menggunakan query ?gid=
         */
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);

            if (isset($query['gid'])) {
                return (string) $query['gid'];
            }
        }

        return '0';
    }

    private function buildPublicCsvUrl(
        string $spreadsheetUrl,
        string $range
    ): string {
        $spreadsheetId = $this->extractSpreadsheetId(
            $spreadsheetUrl
        );

        $gid = $this->extractGid($spreadsheetUrl);

        return 'https://docs.google.com/spreadsheets/d/'
            .$spreadsheetId
            .'/gviz/tq?'
            .http_build_query([
                'tqx' => 'out:csv',
                'gid' => $gid,
                'range' => $range,
            ]);
    }

    private function isPublishedUrl(string $url): bool
    {
        return preg_match(
            '#/spreadsheets/d/e/([a-zA-Z0-9_-]+)#',
            $url
        ) === 1;
    }

    private function buildPublishedCsvUrl(
        string $spreadsheetUrl,
        string $range
    ): string {
        if (
            ! preg_match(
                '#/spreadsheets/d/e/([a-zA-Z0-9_-]+)#',
                $spreadsheetUrl,
                $matches
            )
        ) {
            throw new RuntimeException(
                'URL Publish to Web tidak valid.'
            );
        }

        $publishedId = $matches[1];
        $gid = $this->extractGid($spreadsheetUrl);

        return 'https://docs.google.com/spreadsheets/d/e/'
            .$publishedId
            .'/pub?'
            .http_build_query([
                'output' => 'csv',
                'single' => 'true',
                'gid' => $gid,
                'range' => $range,
            ]);
    }

    /**
     * Contoh:
     * Nama sheet: Buku Agenda 2026
     * Range: A1:K100
     *
     * menjadi:
     * 'Buku Agenda 2026'!A1:K100
     */
    private function buildA1Range(
        string $sheetName,
        string $range
    ): string {
        /*
         * Escape tanda petik tunggal dalam nama sheet.
         */
        $escapedSheetName = str_replace(
            "'",
            "''",
            $sheetName
        );

        return "'{$escapedSheetName}'!{$range}";
    }

    private function parseCsv(string $content): array
    {
        $stream = fopen('php://temp', 'w+');

        if (! $stream) {
            throw new RuntimeException(
                'Buffer CSV tidak dapat dibuat.'
            );
        }

        fwrite($stream, $content);
        rewind($stream);

        $rows = [];

        while (($row = fgetcsv($stream)) !== false) {
            if (
                count($row) === 1
                && trim((string) ($row[0] ?? '')) === ''
            ) {
                continue;
            }

            $rows[] = array_map(
                fn ($value) => is_string($value)
                    ? trim($value)
                    : $value,
                $row
            );
        }

        fclose($stream);

        return $this->normalizeRows($rows);
    }

    /**
     * Output sengaja dibuat sama seperti TabularFileReader.
     */
    private function normalizeRows(array $rows): array
    {
        if ($rows === []) {
            return [
                'headers' => [],
                'rows' => [],
            ];
        }

        $firstRow = array_values($rows[0]);

        $headers = [];

        foreach ($firstRow as $index => $value) {
            $header = trim((string) $value);

            if ($header === '') {
                $header = 'Kolom '.($index + 1);
            }

            /*
             * Hindari duplicate header.
             */
            $original = $header;
            $counter = 2;

            while (in_array($header, $headers, true)) {
                $header = "{$original} ({$counter})";
                $counter++;
            }

            $headers[] = $header;
        }

        $dataRows = [];

        foreach (array_slice($rows, 1) as $row) {
            $row = array_values($row);

            $assoc = [];

            foreach ($headers as $index => $header) {
                $value = $row[$index] ?? '';

                $assoc[$header] = is_scalar($value)
                    ? trim((string) $value)
                    : '';
            }

            $hasValue = collect($assoc)
                ->contains(
                    fn ($value) => trim((string) $value) !== ''
                );

            if ($hasValue) {
                $dataRows[] = $assoc;
            }
        }

        return [
            'headers' => $headers,
            'rows' => $dataRows,
        ];
    }
}
