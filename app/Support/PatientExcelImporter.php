<?php

namespace App\Support;

use App\Models\Patient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class PatientExcelImporter
{
    public static function import(UploadedFile $file): array
    {
        $rows = self::rows($file->getRealPath());
        $headers = array_map(fn ($value) => trim((string) $value), array_shift($rows) ?? []);
        $indexes = array_flip($headers);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $data = self::patientData($row, $indexes);
            $medicalData = self::medicalData($row, $indexes);
            $privacyAccepted = self::boolValue(self::valueAny($row, $indexes, ['Privacy']));

            if (blank($data['first_name']) && blank($data['last_name']) && blank($data['business_name']) && blank($data['email'])) {
                $skipped++;
                continue;
            }

            $data['first_name'] = $data['first_name'] ?: ($data['business_name'] ?: 'Senza nome');
            $data['last_name'] = $data['last_name'] ?: ($data['business_name'] ?: 'Importato');

            $patient = self::findPatient($data, $row, $indexes);

            if ($patient) {
                $patient->update($data);
                $updated++;
            } else {
                $patient = Patient::create($data + ['user_id' => Auth::id()]);
                $created++;
            }

            if (collect($medicalData)->filter(fn ($value) => filled($value))->isNotEmpty()) {
                $patient->medicalRecord()->updateOrCreate([], $medicalData);
            }

            if ($privacyAccepted) {
                $patient->privacyConsent()->updateOrCreate([], [
                    'privacy_policy_accepted' => true,
                    'health_data_processing_accepted' => true,
                    'marketing_accepted' => false,
                    'signed_at' => null,
                    'signature_method' => 'importazione',
                    'document_version' => 'storico',
                    'notes' => 'Importato da archivio Excel.',
                ]);
            }
        }

        return compact('created', 'updated', 'skipped');
    }

    private static function patientData(array $row, array $indexes): array
    {
        [$address, $streetNumber] = PatientAddressNormalizer::splitStreetNumber(self::valueAny($row, $indexes, ['Indirizzo', 'Residenza via/piazza']));

        return PatientAddressNormalizer::normalize([
            'legacy_patient_id' => self::integerValue(self::valueAny($row, $indexes, ['ID', 'Idpaziente', 'Codice cliente'])),
            'customer_type' => self::valueAny($row, $indexes, ['Tipo cliente']) ?: 'Privato',
            'telematic_address' => self::valueAny($row, $indexes, ['Indirizzo telematico (Codice SDI o PEC)']) ?: '0000000',
            'email' => self::valueAny($row, $indexes, ['Email', 'E-mail']),
            'pec' => self::valueAny($row, $indexes, ['PEC']),
            'phone' => self::limit(self::valueAny($row, $indexes, ['Telefono']), 255),
            'country_id' => self::limit(self::valueAny($row, $indexes, ['ID Paese', 'Nazione']) ?: 'IT', 2),
            'vat_number' => self::limit(self::valueAny($row, $indexes, ['Partita Iva', 'Partita IVA']), 32),
            'fiscal_code' => self::limit(self::valueAny($row, $indexes, ['Codice fiscale']), 16),
            'business_name' => self::valueAny($row, $indexes, ['Denominazione']),
            'first_name' => self::valueAny($row, $indexes, ['Nome']),
            'last_name' => self::valueAny($row, $indexes, ['Cognome']),
            'birth_date' => self::dateValue(self::valueAny($row, $indexes, ['Data di nascita'])),
            'gender' => self::limit(self::valueAny($row, $indexes, ['Sesso']), 50),
            'birth_place' => self::valueAny($row, $indexes, ['Luogo di nascita']),
            'profession' => self::valueAny($row, $indexes, ['Professione']),
            'eori_code' => self::limit(self::valueAny($row, $indexes, ['Codice EORI (solo Privati)']), 32),
            'postal_code' => self::limit(self::valueAny($row, $indexes, ['CAP']), 10),
            'province' => self::limit(self::valueAny($row, $indexes, ['Provincia']), 2),
            'city' => self::valueAny($row, $indexes, ['Comune']),
            'address' => $address,
            'street_number' => self::limit(self::valueAny($row, $indexes, ['Numero civico', 'Numero Civico']) ?: $streetNumber, 20),
        ]);
    }

    private static function findPatient(array $data, array $row, array $indexes): ?Patient
    {
        $legacyPatientId = $data['legacy_patient_id'] ?? null;
        if ($legacyPatientId) {
            $patient = Patient::where('user_id', Auth::id())
                ->where('legacy_patient_id', $legacyPatientId)
                ->first();

            if ($patient) {
                return $patient;
            }
        }

        $patientId = self::valueAny($row, $indexes, ['Codice cliente']);
        if (filled($patientId)) {
            $patient = Patient::where('user_id', Auth::id())->whereKey($patientId)->first();

            if ($patient) {
                return $patient;
            }
        }

        if (filled($data['fiscal_code'] ?? null)) {
            $patient = Patient::where('user_id', Auth::id())
                ->where('fiscal_code', $data['fiscal_code'])
                ->first();

            if ($patient) {
                return $patient;
            }
        }

        if (filled($data['first_name'] ?? null) && filled($data['last_name'] ?? null) && filled($data['birth_date'] ?? null)) {
            return Patient::where('user_id', Auth::id())
                ->where('first_name', $data['first_name'])
                ->where('last_name', $data['last_name'])
                ->whereDate('birth_date', $data['birth_date'])
                ->first();
        }

        return null;
    }

    private static function medicalData(array $row, array $indexes): array
    {
        return [
            'reason_for_visit' => self::valueAny($row, $indexes, ['Motivo del consulto']),
            'symptoms_started_at' => self::valueAny($row, $indexes, ['Data inizio sintomatologia']),
            'pain_description' => self::valueAny($row, $indexes, ['Descrizione e irradiazione del dolore']),
            'exams' => self::valueAny($row, $indexes, ['Indagini eseguite']),
            'previous_treatments' => self::valueAny($row, $indexes, ['Trattamenti eseguiti']),
            'traumas' => self::valueAny($row, $indexes, ['Traumi']),
            'surgeries' => self::valueAny($row, $indexes, ['Chirurgie']),
            'visceral_issues' => self::valueAny($row, $indexes, ['Visceri']),
            'prosthesis_and_devices' => self::valueAny($row, $indexes, ['Protesi/vista/plantari']),
            'orthodontics' => self::valueAny($row, $indexes, ['Ortodonzia']),
            'family_history' => self::valueAny($row, $indexes, ['Anamnesi familiare/parto']),
            'lifestyle' => self::valueAny($row, $indexes, ['Abitudini di vita/sport']),
            'physical_sphere' => self::valueAny($row, $indexes, ['Sfera fisica e psichica', 'Sfera fisica e psichica1']),
            'medications' => self::valueAny($row, $indexes, ['Farmaci', 'Farmaci1']),
        ];
    }

    private static function rows(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File Excel non leggibile.');
        }

        $sharedStrings = self::sharedStrings($zip);
        $sheetPath = self::sheetPath($zip) ?: 'xl/worksheets/sheet1.xml';
        $xml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('Foglio ImportAnagrafiche non trovato.');
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
            if ((string) $sheet['name'] === 'ImportAnagrafiche') {
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
                return self::cleanText($value);
            }
        }

        return '';
    }

    private static function cleanText(string $value): string
    {
        return trim(preg_replace('/_x000D_/i', "\n", $value) ?? $value);
    }

    private static function dateValue(string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (new \DateTimeImmutable('1899-12-30'))->modify('+'.(int) $value.' days')->format('Y-m-d');
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private static function boolValue(string $value): bool
    {
        return in_array(strtolower($value), ['1', 'true', 'vero', 'si', 'sì', 'yes'], true);
    }

    private static function limit(string $value, int $length): string
    {
        return mb_substr($value, 0, $length);
    }

    private static function integerValue(string $value): ?int
    {
        return filled($value) && is_numeric($value) ? (int) $value : null;
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
