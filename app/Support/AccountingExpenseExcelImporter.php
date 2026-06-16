<?php

namespace App\Support;

use App\Models\AccountingExpense;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class AccountingExpenseExcelImporter
{
    public static function importYear(UploadedFile $file, int $year): int
    {
        $created = 0;

        foreach (self::tables($file->getRealPath()) as [$headers, $rows]) {
            $indexes = array_flip($headers);

            foreach ($rows as $row) {
                $date = self::dateValue(self::valueAny($row, $indexes, ['data', 'data spesa', 'data documento', 'data operazione', 'data contabile', 'data valuta', 'date']));
                $month = self::monthValue(self::valueAny($row, $indexes, ['mese', 'month']), $date);
                $amount = self::amountValue(self::valueAny($row, $indexes, ['uscite', 'uscita', 'spese', 'spesa', 'costi', 'costo', 'pagamenti', 'pagamento', 'addebiti', 'addebito', 'dare', 'importo uscita', 'importo spesa', 'totale uscita', 'importo', 'totale', 'amount']));
                $description = self::valueAny($row, $indexes, ['causale', 'descrizione', 'descrizione operazione', 'fornitore', 'dettaglio', 'beneficiario', 'description']);

                if (($amount === null && blank($description) && blank($date)) || $month === null) {
                    continue;
                }

                AccountingExpense::create([
                    'user_id' => Auth::id(),
                    'year' => $year,
                    'month' => $month,
                    'expense_date' => $date,
                    'description' => $description ?: 'Spesa importata',
                    'amount' => $amount ?? 0,
                ]);

                $created++;
            }
        }

        return $created;
    }

    public static function import(UploadedFile $file, int $year, int $month): int
    {
        $created = 0;

        foreach (self::tables($file->getRealPath()) as [$headers, $rows]) {
            $indexes = array_flip($headers);

            foreach ($rows as $row) {
                $amount = self::amountValue(self::valueAny($row, $indexes, ['uscite', 'uscita', 'spese', 'spesa', 'costi', 'costo', 'pagamenti', 'pagamento', 'addebiti', 'addebito', 'dare', 'importo uscita', 'importo spesa', 'totale uscita', 'importo', 'totale', 'amount']));
                $description = self::valueAny($row, $indexes, ['causale', 'descrizione', 'descrizione operazione', 'fornitore', 'dettaglio', 'beneficiario', 'description']);
                $date = self::dateValue(self::valueAny($row, $indexes, ['data', 'data spesa', 'data documento', 'data operazione', 'data contabile', 'data valuta', 'date']));

                if ($amount === null && blank($description) && blank($date)) {
                    continue;
                }

                AccountingExpense::create([
                    'user_id' => Auth::id(),
                    'year' => $year,
                    'month' => $month,
                    'expense_date' => $date,
                    'description' => $description ?: 'Spesa importata',
                    'amount' => $amount ?? 0,
                ]);

                $created++;
            }
        }

        return $created;
    }

    private static function tables(string $path): array
    {
        return array_values(array_filter(array_map(function (array $rows) {
            return self::tableFromRows($rows);
        }, self::worksheets($path))));
    }

    private static function tableFromRows(array $rows): ?array
    {
        foreach ($rows as $index => $row) {
            $headers = array_map(fn ($value) => self::normalizeHeader((string) $value), $row);
            $score = collect($headers)->filter(fn (string $header) => in_array($header, [
                'data', 'data spesa', 'data documento', 'data operazione', 'data contabile', 'data valuta',
                'mese', 'uscite', 'uscita', 'spese', 'spesa', 'costi', 'costo', 'pagamenti', 'pagamento',
                'addebiti', 'addebito', 'dare', 'importo uscita', 'importo spesa', 'totale uscita', 'importo', 'totale',
                'causale', 'descrizione', 'descrizione operazione', 'fornitore', 'beneficiario',
            ], true))->count();

            if ($score >= 2) {
                return [$headers, array_slice($rows, $index + 1)];
            }
        }

        if ($rows === []) {
            return null;
        }

        return [
            array_map(fn ($value) => self::normalizeHeader((string) $value), array_shift($rows) ?? []),
            $rows,
        ];
    }

    private static function worksheets(string $path): array
    {
        if (self::isLegacyXls($path)) {
            return [LegacyXlsReader::rows($path)];
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File Excel spese non leggibile.');
        }

        $sharedStrings = self::sharedStrings($zip);
        $sheetNames = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                $sheetNames[] = $name;
            }
        }

        natsort($sheetNames);
        $worksheets = [];

        foreach ($sheetNames as $sheetName) {
            $xml = $zip->getFromName($sheetName);
            if ($xml !== false) {
                $worksheets[] = self::worksheetRows($xml, $sharedStrings);
            }
        }

        $zip->close();

        return $worksheets;
    }

    private static function rows(string $path): array
    {
        return self::worksheets($path)[0] ?? [];
    }

    private static function worksheetRows(string $xml, array $sharedStrings): array
    {
        $worksheet = new SimpleXMLElement($xml);
        $worksheet->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $parsedRows = [];

        foreach ($worksheet->xpath('//main:sheetData/main:row') as $row) {
            $row->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $cells = [];
            $maxIndex = 0;

            foreach ($row->xpath('main:c') as $cell) {
                $ref = (string) $cell['r'];
                $index = self::columnIndex(preg_replace('/\d+/', '', $ref));
                $cells[$index] = self::cellValue($cell, $sharedStrings);
                $maxIndex = max($maxIndex, $index);
            }

            $parsedRows[] = array_map(fn ($index) => $cells[$index] ?? '', range(0, $maxIndex));
        }

        return $parsedRows;
    }

    private static function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $shared = new SimpleXMLElement($xml);
        $shared->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        return array_map(fn ($item) => trim((string) $item), $shared->xpath('//main:si/main:t') ?: []);
    }

    private static function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];

        if ($type === 'inlineStr') {
            $cell->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            return trim((string) (($cell->xpath('main:is/main:t')[0] ?? null)));
        }

        $value = trim((string) ($cell->v ?? ''));

        return $type === 's' ? ($sharedStrings[(int) $value] ?? '') : $value;
    }

    private static function valueAny(array $row, array $indexes, array $headers): string
    {
        foreach ($headers as $header) {
            $index = $indexes[self::normalizeHeader($header)] ?? null;
            $value = $index === null ? '' : trim((string) ($row[$index] ?? ''));

            if (filled($value)) {
                return str_replace('_x000d_', "\n", $value);
            }
        }

        return '';
    }

    private static function dateValue(string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (new DateTimeImmutable('1899-12-30'))->modify('+'.(int) $value.' days')->format('Y-m-d');
        }

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, trim($value));
            if ($date instanceof DateTimeImmutable) {
                return $date->format('Y-m-d');
            }
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private static function monthValue(string $value, ?string $date): ?int
    {
        if ($date) {
            return (int) date('n', strtotime($date));
        }

        if (blank($value)) {
            return null;
        }

        $normalized = strtolower(trim($value));
        $months = [
            'gennaio' => 1, 'gen' => 1, 'febbraio' => 2, 'feb' => 2, 'marzo' => 3, 'mar' => 3,
            'aprile' => 4, 'apr' => 4, 'maggio' => 5, 'mag' => 5, 'giugno' => 6, 'giu' => 6,
            'luglio' => 7, 'lug' => 7, 'agosto' => 8, 'ago' => 8, 'settembre' => 9, 'set' => 9,
            'ottobre' => 10, 'ott' => 10, 'novembre' => 11, 'nov' => 11, 'dicembre' => 12, 'dic' => 12,
        ];

        if (isset($months[$normalized])) {
            return $months[$normalized];
        }

        return is_numeric($normalized) && (int) $normalized >= 1 && (int) $normalized <= 12
            ? (int) $normalized
            : null;
    }

    private static function amountValue(string $value): ?float
    {
        if (blank($value)) {
            return null;
        }

        $normalized = str_replace(['EUR', '€', 'â‚¬', 'Ã¢â€šÂ¬', ' '], '', $value);
        $normalized = str_contains($normalized, ',')
            ? str_replace(',', '.', str_replace('.', '', $normalized))
            : $normalized;

        return is_numeric($normalized) ? abs((float) $normalized) : null;
    }

    private static function normalizeHeader(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private static function isLegacyXls(string $path): bool
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            return false;
        }

        $signature = fread($handle, 8);
        fclose($handle);

        return $signature === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
    }

    private static function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }
}
