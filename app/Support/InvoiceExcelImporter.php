<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Patient;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class InvoiceExcelImporter
{
    public static function import(UploadedFile $file): array
    {
        $rows = self::rows($file->getRealPath());
        $headers = array_map(fn ($value) => trim((string) $value), array_shift($rows) ?? []);
        $indexes = array_flip($headers);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $unmatched = 0;

        foreach ($rows as $row) {
            $patientId = self::valueAny($row, $indexes, ['Idpaziente', 'ID paziente', 'Codice cliente']);
            $number = self::normalizeNumber(self::valueAny($row, $indexes, ['N Fattura', 'Numero fattura', 'Numero']));
            $issuedAt = self::dateValue(self::valueAny($row, $indexes, ['Data documento', 'Data di emissione', 'Data', 'Data fattura']));
            $paymentDate = self::dateValue(self::valueAny($row, $indexes, ['Data incasso', 'Data pagamento', 'Data di pagamento', 'Data scadenza pagamento']));

            if (blank($patientId) && blank($number) && blank($issuedAt)) {
                $skipped++;
                continue;
            }

            $patient = self::findPatient(
                $patientId,
                self::valueAny($row, $indexes, ['Codice Fiscale', 'CF']),
                self::valueAny($row, $indexes, ['Cliente', 'Paziente'])
            );

            if (! $patient || ! $issuedAt) {
                $unmatched++;
                continue;
            }

            $amount = self::amountValue(self::valueAny($row, $indexes, ['Totale documento', 'Totale', 'Netto a pagare']))
                ?? self::calculatedTotal($row, $indexes)
                ?? 0;
            $lineAmount = self::amountValue(self::valueAny($row, $indexes, ['Importo', 'Totale non soggetto IVA (N2)', 'Totale imponibile']));

            $year = (int) date('Y', strtotime($issuedAt));
            $progressive = self::progressiveFromNumber($number);

            $data = [
                'number' => $number ?: null,
                'year' => self::yearFromNumber($number) ?: $year,
                'progressive_number' => $progressive,
                'issued_at' => $issuedAt,
                'service' => self::valueAny($row, $indexes, ['Descrizione', 'Prestazione', 'Servizio']) ?: 'Seduta di manipolazione osteopatica',
                'line_amount' => $lineAmount,
                'amount' => $amount,
                'payment_method' => self::valueAny($row, $indexes, ['Metodo pagamento', 'Metodo di pagamento']),
                'payment_date' => $paymentDate ?: $issuedAt,
                'status' => self::statusValue(
                    self::valueAny($row, $indexes, ['Incassi'])
                    ?: self::valueAny($row, $indexes, ['Stato'])
                ),
                'description' => self::description($row, $indexes),
            ];

            $invoice = filled($number) ? self::findInvoice($number) : null;

            if ($invoice) {
                $invoice->patient()->associate($patient);
                $invoice->fill($data);
                $invoice->save();
                $updated++;
            } else {
                $patient->invoices()->create($data);
                $created++;
            }
        }

        return compact('created', 'updated', 'skipped', 'unmatched');
    }

    private static function findPatient(string $patientId, string $fiscalCode = '', string $customerName = ''): ?Patient
    {
        if (filled($patientId) && is_numeric($patientId)) {
            $legacyId = (int) $patientId;

            $patient = Patient::where('user_id', Auth::id())
                ->where('legacy_patient_id', $legacyId)
                ->first()
                ?? self::findPatientByInternalIdOnlyWhenNoLegacyArchiveExists($legacyId);

            if ($patient) {
                return $patient;
            }
        }

        if (filled($fiscalCode)) {
            $patient = Patient::where('user_id', Auth::id())
                ->where('fiscal_code', strtoupper(trim($fiscalCode)))
                ->first();

            if ($patient) {
                return $patient;
            }
        }

        return self::findPatientByName($customerName);
    }

    private static function findPatientByName(string $customerName): ?Patient
    {
        $normalizedName = self::normalizeName($customerName);

        if (blank($normalizedName)) {
            return null;
        }

        $matches = Patient::where('user_id', Auth::id())
            ->get()
            ->filter(function (Patient $patient) use ($normalizedName) {
                return in_array($normalizedName, [
                    self::normalizeName($patient->list_name),
                    self::normalizeName(trim($patient->first_name.' '.$patient->last_name)),
                    self::normalizeName(trim($patient->last_name.' '.$patient->first_name)),
                ], true);
            })
            ->values();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private static function findPatientByInternalIdOnlyWhenNoLegacyArchiveExists(int $patientId): ?Patient
    {
        $hasLegacyArchive = Patient::where('user_id', Auth::id())
            ->whereNotNull('legacy_patient_id')
            ->exists();

        if ($hasLegacyArchive) {
            return null;
        }

        return Patient::where('user_id', Auth::id())->whereKey($patientId)->first();
    }

    private static function findInvoice(string $number): ?Invoice
    {
        return Invoice::where('number', $number)
            ->whereHas('patient', fn ($query) => $query->where('user_id', Auth::id()))
            ->first();
    }

    private static function description(array $row, array $indexes): string
    {
        $parts = [];

        foreach ([
            'IDFattura' => ['IDFattura', 'ID fattura'],
            'Importo' => ['Importo'],
            'Inps' => ['Inps', 'INPS', 'Cassa'],
            'Bollo' => ['Bollo'],
            'File XML' => ['Nome file'],
            'Stato invio' => ['Stato'],
        ] as $label => $headers) {
            $value = self::valueAny($row, $indexes, $headers);
            if (filled($value)) {
                $parts[] = "{$label}: {$value}";
            }
        }

        return implode(' | ', $parts);
    }

    private static function calculatedTotal(array $row, array $indexes): ?float
    {
        $amount = self::amountValue(self::valueAny($row, $indexes, ['Importo', 'Totale non soggetto IVA (N2)', 'Totale imponibile']));

        if ($amount === null) {
            return null;
        }

        return $amount
            + (self::amountValue(self::valueAny($row, $indexes, ['Inps', 'INPS', 'Cassa'])) ?? 0)
            + (self::amountValue(self::valueAny($row, $indexes, ['Bollo'])) ?? 0);
    }

    private static function rows(string $path): array
    {
        if (self::isLegacyXls($path)) {
            return LegacyXlsReader::rows($path);
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File Excel fatture non leggibile.');
        }

        $sharedStrings = self::sharedStrings($zip);
        $sheetPath = self::sheetPath($zip) ?: 'xl/worksheets/sheet1.xml';
        $xml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('Foglio Fatture non trovato.');
        }

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

    private static function sheetPath(ZipArchive $zip): ?string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            return null;
        }

        $workbook = new SimpleXMLElement($workbookXml);
        $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationshipId = null;

        foreach ($workbook->xpath('//main:sheet') as $sheet) {
            if ((string) $sheet['name'] === 'Fatture') {
                $attributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $relationshipId = (string) $attributes['id'];
                break;
            }
        }

        if (! $relationshipId) {
            $firstSheet = $workbook->xpath('//main:sheet')[0] ?? null;
            if (! $firstSheet) {
                return null;
            }

            $attributes = $firstSheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $relationshipId = (string) $attributes['id'];
        }

        $rels = new SimpleXMLElement($relsXml);
        foreach ($rels->Relationship as $relationship) {
            if ((string) $relationship['Id'] === $relationshipId) {
                return 'xl/'.ltrim((string) $relationship['Target'], '/');
            }
        }

        return null;
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

    private static function value(array $row, array $indexes, string $header): string
    {
        $index = $indexes[$header] ?? null;

        return $index === null ? '' : trim((string) ($row[$index] ?? ''));
    }

    private static function valueAny(array $row, array $indexes, array $headers): string
    {
        foreach ($headers as $header) {
            $value = self::value($row, $indexes, $header);
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

        $timestamp = strtotime($value);

        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private static function amountValue(string $value): ?float
    {
        if (blank($value)) {
            return null;
        }

        $normalized = str_replace(['EUR', ' '], '', $value);
        $normalized = str_contains($normalized, ',')
            ? str_replace(',', '.', str_replace('.', '', $normalized))
            : $normalized;

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private static function progressiveFromNumber(string $number): ?int
    {
        if (preg_match('/^\s*0*(\d+)/', $number, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private static function yearFromNumber(string $number): ?int
    {
        if (preg_match('/\/\s*(\d{2})\s*$/', $number, $matches)) {
            return 2000 + (int) $matches[1];
        }

        if (preg_match('/(\d{4})\s*$/', $number, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private static function statusValue(string $value): string
    {
        return match (strtolower(trim($value))) {
            'incassata', 'pagata', 'paid' => 'paid',
            'bozza', 'draft' => 'draft',
            'inviata', 'emessa', 'sent' => 'sent',
            'annullata', 'cancelled', 'scartata' => 'cancelled',
            default => 'paid',
        };
    }

    private static function normalizeNumber(string $number): string
    {
        $number = trim($number);

        if (preg_match('/FPR\s*0*(\d+)\s*\/\s*(\d{2,4})/i', $number, $matches)) {
            $year = strlen($matches[2]) === 2 ? '20'.$matches[2] : $matches[2];

            return ((int) $matches[1]).'/'.$year;
        }

        return preg_replace('/^0+(\d+\/)/', '$1', $number) ?? $number;
    }

    private static function normalizeName(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^\pL\pN\s]+/u', ' ', $value) ?? '';

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
