<?php

namespace App\Services\Imports;

use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

class LegacySpreadsheetReader
{
    /**
     * @return array{headers: array<int, string>, rows: array<int, array{sheet:string,row_number:int,data:array<string,mixed>}>}
     */
    public function read(string $path, string $extension): array
    {
        return match (strtolower($extension)) {
            'csv' => $this->readCsv($path),
            'xlsx' => $this->readXlsx($path),
            default => throw ValidationException::withMessages([
                'upload' => 'Only CSV and XLSX files are supported for legacy migration.',
            ]),
        };
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');

        if (! $handle) {
            throw new RuntimeException('The uploaded CSV file could not be opened.');
        }

        try {
            $headers = null;
            $rows = [];
            $rowNumber = 0;

            while (($values = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($rowNumber === 1 && isset($values[0])) {
                    $values[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $values[0]);
                }

                if ($headers === null) {
                    $headers = $this->normalizeHeaders($values);
                    continue;
                }

                if ($this->isBlankRow($values)) {
                    continue;
                }

                $rows[] = [
                    'sheet' => 'CSV',
                    'row_number' => $rowNumber,
                    'data' => $this->combineRow($headers, $values),
                ];
            }

            return [
                'headers' => $headers ?? [],
                'rows' => $rows,
            ];
        } finally {
            fclose($handle);
        }
    }

    private function readXlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages([
                'upload' => 'XLSX import requires the PHP zip extension. Enable ext-zip or convert the legacy workbook to CSV.',
            ]);
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages([
                'upload' => 'The uploaded XLSX workbook is invalid or could not be opened.',
            ]);
        }

        try {
            $sharedStrings = $this->sharedStrings($zip);
            $sheetNames = $this->sheetNames($zip);
            $worksheetFiles = $this->worksheetFiles($zip);
            $allHeaders = [];
            $rows = [];

            foreach ($worksheetFiles as $index => $worksheetFile) {
                $xml = $zip->getFromName($worksheetFile);

                if ($xml === false) {
                    continue;
                }

                $matrix = $this->parseWorksheet($xml, $sharedStrings);

                if ($matrix === []) {
                    continue;
                }

                $headerRowIndex = $this->findHeaderRowIndex($matrix);

                if ($headerRowIndex === null) {
                    continue;
                }

                $headers = $this->normalizeHeaders($matrix[$headerRowIndex]);

                if ($allHeaders === []) {
                    $allHeaders = $headers;
                } else {
                    foreach ($headers as $header) {
                        if (! in_array($header, $allHeaders, true)) {
                            $allHeaders[] = $header;
                        }
                    }
                }

                $sheetName = $sheetNames[$index] ?? ('Sheet '.($index + 1));

                foreach ($matrix as $matrixIndex => $values) {
                    if ($matrixIndex <= $headerRowIndex || $this->isBlankRow($values)) {
                        continue;
                    }

                    $rows[] = [
                        'sheet' => $sheetName,
                        'row_number' => $matrixIndex + 1,
                        'data' => $this->combineRow($headers, $values),
                    ];
                }
            }

            if ($allHeaders === []) {
                throw ValidationException::withMessages([
                    'upload' => 'No usable tabular data was found in the XLSX workbook.',
                ]);
            }

            return [
                'headers' => $allHeaders,
                'rows' => $rows,
            ];
        } finally {
            $zip->close();
        }
    }

    /** @return array<int, string> */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $strings = [];

        if (preg_match_all('/<si\b[^>]*>(.*?)<\/si>/si', $xml, $items)) {
            foreach ($items[1] as $item) {
                $parts = [];

                if (preg_match_all('/<t\b[^>]*>(.*?)<\/t>/si', $item, $texts)) {
                    foreach ($texts[1] as $text) {
                        $parts[] = $this->decodeXmlText($text);
                    }
                }

                $strings[] = implode('', $parts);
            }
        }

        return $strings;
    }

    /** @return array<int, string> */
    private function sheetNames(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/workbook.xml');

        if ($xml === false) {
            return [];
        }

        $names = [];

        if (preg_match_all('/<sheet\b[^>]*name="([^"]+)"[^>]*\/>/si', $xml, $matches)) {
            foreach ($matches[1] as $name) {
                $names[] = $this->decodeXmlText($name);
            }
        }

        return $names;
    }

    /** @return array<int, string> */
    private function worksheetFiles(ZipArchive $zip): array
    {
        $files = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if ($name && preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                $files[] = $name;
            }
        }

        usort($files, function (string $left, string $right): int {
            preg_match('/sheet(\d+)\.xml$/', $left, $leftMatch);
            preg_match('/sheet(\d+)\.xml$/', $right, $rightMatch);

            return ((int) ($leftMatch[1] ?? 0)) <=> ((int) ($rightMatch[1] ?? 0));
        });

        return $files;
    }

    /** @return array<int, array<int, mixed>> */
    private function parseWorksheet(string $xml, array $sharedStrings): array
    {
        $rows = [];

        if (! preg_match_all('/<row\b[^>]*r="?(\d+)"?[^>]*>(.*?)<\/row>/si', $xml, $rowMatches, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($rowMatches as $rowMatch) {
            $rowNumber = max(1, (int) $rowMatch[1]);
            $cells = [];

            if (preg_match_all('/<c\b([^>]*)>(.*?)<\/c>/si', $rowMatch[2], $cellMatches, PREG_SET_ORDER)) {
                foreach ($cellMatches as $cellMatch) {
                    $attributes = $cellMatch[1];
                    $body = $cellMatch[2];
                    $reference = null;
                    $type = null;

                    if (preg_match('/\br="([A-Z]+)\d+"/i', $attributes, $referenceMatch)) {
                        $reference = strtoupper($referenceMatch[1]);
                    }

                    if (preg_match('/\bt="([^"]+)"/i', $attributes, $typeMatch)) {
                        $type = $typeMatch[1];
                    }

                    if ($reference === null) {
                        continue;
                    }

                    $columnIndex = $this->columnIndex($reference);
                    $value = '';

                    if ($type === 'inlineStr') {
                        if (preg_match_all('/<t\b[^>]*>(.*?)<\/t>/si', $body, $inlineMatches)) {
                            $value = implode('', array_map(
                                fn (string $text): string => $this->decodeXmlText($text),
                                $inlineMatches[1]
                            ));
                        }
                    } elseif (preg_match('/<v\b[^>]*>(.*?)<\/v>/si', $body, $valueMatch)) {
                        $raw = $this->decodeXmlText($valueMatch[1]);

                        if ($type === 's') {
                            $value = $sharedStrings[(int) $raw] ?? '';
                        } elseif ($type === 'b') {
                            $value = $raw === '1' ? '1' : '0';
                        } else {
                            $value = $raw;
                        }
                    }

                    $cells[$columnIndex] = $value;
                }
            }

            if ($cells !== []) {
                $maxColumn = max(array_keys($cells));
                $values = [];

                for ($column = 0; $column <= $maxColumn; $column++) {
                    $values[] = $cells[$column] ?? '';
                }

                $rows[$rowNumber - 1] = $values;
            }
        }

        if ($rows === []) {
            return [];
        }

        ksort($rows);
        $filled = [];
        $lastIndex = max(array_keys($rows));

        for ($index = 0; $index <= $lastIndex; $index++) {
            $filled[$index] = $rows[$index] ?? [];
        }

        return $filled;
    }

    private function findHeaderRowIndex(array $matrix): ?int
    {
        foreach ($matrix as $index => $values) {
            $nonBlank = count(array_filter($values, fn ($value): bool => trim((string) $value) !== ''));

            if ($nonBlank >= 2) {
                return $index;
            }
        }

        return null;
    }

    /** @param array<int, mixed> $headers */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        $seen = [];

        foreach ($headers as $index => $header) {
            $value = trim(preg_replace('/\s+/', ' ', (string) $header));
            $value = $value !== '' ? $value : 'Column '.($index + 1);
            $key = mb_strtolower($value);

            if (isset($seen[$key])) {
                $seen[$key]++;
                $value .= ' #'.$seen[$key];
            } else {
                $seen[$key] = 1;
            }

            $normalized[] = $value;
        }

        return $normalized;
    }

    private function combineRow(array $headers, array $values): array
    {
        $data = [];

        foreach ($headers as $index => $header) {
            $value = $values[$index] ?? null;
            $data[$header] = is_string($value) ? trim($value) : $value;
        }

        return $data;
    }

    private function isBlankRow(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function decodeXmlText(string $value): string
    {
        return html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;

        foreach (str_split(strtoupper($letters)) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }
}
