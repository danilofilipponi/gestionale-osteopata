<?php

namespace App\Support;

use Illuminate\Support\Collection;
use ZipArchive;

class PatientExcelExporter
{
    public static function make(Collection $patients): string
    {
        $headers = [
            'Codice cliente',
            'Tipo cliente',
            'Indirizzo telematico (Codice SDI o PEC)',
            'Email',
            'PEC',
            'Telefono',
            'ID Paese',
            'Partita Iva   ',
            'Codice fiscale',
            'Denominazione',
            'Nome',
            'Cognome',
            'Codice EORI (solo Privati)',
            'Nazione',
            'CAP',
            'Provincia',
            'Comune',
            'Indirizzo',
            'Numero civico',
            'Beneficiario',
            'Condizioni di pagamento',
            'Metodo di pagamento',
            'Banca',
        ];

        $rows = [$headers];

        foreach ($patients as $patient) {
            $beneficiary = filled($patient->business_name)
                ? $patient->business_name
                : trim($patient->last_name.' '.$patient->first_name);

            $rows[] = [
                $patient->id,
                $patient->customer_type ?: 'Privato',
                $patient->telematic_address ?: '0000000',
                $patient->email,
                $patient->pec,
                $patient->phone,
                $patient->country_id ?: 'IT',
                $patient->vat_number,
                $patient->fiscal_code,
                $patient->business_name,
                $patient->first_name,
                $patient->last_name,
                $patient->eori_code,
                $patient->country_id ?: 'IT',
                $patient->postal_code,
                $patient->province,
                $patient->city,
                $patient->address,
                $patient->street_number,
                $beneficiary,
                '',
                '',
                '',
            ];
        }

        $values = [
            ['Tipo Cliente', 'CodicePaese', 'Provincia', 'CondizioniPagamento'],
        ];

        for ($index = 0; $index < 249; $index++) {
            $values[] = [
                ['Privato', 'Pubblica amministrazione'][$index] ?? '',
                self::countryCodes()[$index] ?? '',
                self::provinceCodes()[$index] ?? '',
                self::paymentTerms()[$index] ?? '',
            ];
        }

        return self::xlsx([
            'ImportAnagrafiche' => $rows,
            'Valori' => $values,
        ]);
    }

    private static function xlsx(array $sheets): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'patients-export-');
        $zip = new ZipArchive();
        $zip->open($temp, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', self::contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', self::rels());
        $zip->addFromString('xl/workbook.xml', self::workbook(array_keys($sheets)));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels(count($sheets)));
        $zip->addFromString('xl/styles.xml', self::styles());

        $index = 1;
        foreach ($sheets as $rows) {
            $zip->addFromString("xl/worksheets/sheet{$index}.xml", self::worksheet($rows, $index === 1));
            $index++;
        }

        $zip->close();
        $content = file_get_contents($temp);
        unlink($temp);

        return $content;
    }

    private static function worksheet(array $rows, bool $withImportValidations = false): string
    {
        $xmlRows = [];
        foreach ($rows as $rowIndex => $row) {
            $cells = [];
            foreach ($row as $columnIndex => $value) {
                $cell = self::columnName($columnIndex + 1).($rowIndex + 1);
                $style = $rowIndex === 0 ? ' s="1"' : '';
                $cells[] = '<c r="'.$cell.'" t="inlineStr"'.$style.'><is><t>'.self::escape((string) $value).'</t></is></c>';
            }
            $xmlRows[] = '<row r="'.($rowIndex + 1).'">'.implode('', $cells).'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<sheetData>'.implode('', $xmlRows).'</sheetData>'
            .($withImportValidations ? self::importValidations() : '')
            .'</worksheet>';
    }

    private static function importValidations(): string
    {
        return '<dataValidations count="4">'
            .'<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="B2:B1000"><formula1>Valori!$A$2:$A$3</formula1></dataValidation>'
            .'<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="P2:P1000"><formula1>Valori!$C$2:$C$112</formula1></dataValidation>'
            .'<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="G2:G1000 N2:N1000"><formula1>Valori!$B$2:$B$250</formula1></dataValidation>'
            .'<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="U2:U1000"><formula1>Valori!$D$2:$D$4</formula1></dataValidation>'
            .'</dataValidations>';
    }

    private static function workbook(array $sheetNames): string
    {
        $sheets = [];
        foreach ($sheetNames as $index => $name) {
            $sheetId = $index + 1;
            $sheets[] = '<sheet name="'.self::escape($name).'" sheetId="'.$sheetId.'" r:id="rId'.$sheetId.'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.implode('', $sheets).'</sheets></workbook>';
    }

    private static function workbookRels(int $sheetCount): string
    {
        $rels = [];
        for ($index = 1; $index <= $sheetCount; $index++) {
            $rels[] = '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>';
        }
        $rels[] = '<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.implode('', $rels).'</Relationships>';
    }

    private static function contentTypes(int $sheetCount): string
    {
        $overrides = [];
        for ($index = 1; $index <= $sheetCount; $index++) {
            $overrides[] = '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .implode('', $overrides)
            .'</Types>';
    }

    private static function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF5C8D83"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="1" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
            .'</styleSheet>';
    }

    private static function countryCodes(): array
    {
        return [
            'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
            'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS',
            'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN',
            'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE',
            'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF',
            'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM',
            'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM',
            'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC',
            'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK',
            'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA',
            'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG',
            'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW',
            'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS',
            'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO',
            'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI',
            'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'ZA', 'ZM', 'ZW',
        ];
    }

    private static function provinceCodes(): array
    {
        return [
            'AG', 'AL', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AT', 'AV', 'BA', 'BG', 'BI', 'BL', 'BN', 'BO', 'BR',
            'BS', 'BT', 'BZ', 'CA', 'CB', 'CE', 'CH', 'CI', 'CL', 'CN', 'CO', 'CR', 'CS', 'CT', 'CZ', 'EN',
            'FC', 'FE', 'FG', 'FI', 'FM', 'FR', 'GE', 'GO', 'GR', 'IM', 'IS', 'KR', 'LC', 'LE', 'LI', 'LO',
            'LT', 'LU', 'MB', 'MC', 'ME', 'MI', 'MN', 'MO', 'MS', 'MT', 'NA', 'NO', 'NU', 'OG', 'OR', 'OT',
            'PA', 'PC', 'PD', 'PE', 'PG', 'PI', 'PN', 'PO', 'PR', 'PT', 'PU', 'PV', 'PZ', 'RA', 'RC', 'RE',
            'RG', 'RI', 'RM', 'RN', 'RO', 'SA', 'SI', 'SO', 'SP', 'SR', 'SS', 'SU', 'SV', 'TA', 'TE', 'TN',
            'TO', 'TP', 'TR', 'TS', 'TV', 'UD', 'VA', 'VB', 'VC', 'VE', 'VI', 'VR', 'VS', 'VT', 'VV',
        ];
    }

    private static function paymentTerms(): array
    {
        return ['Pagamento a rate', 'Pagamento completo', 'Anticipo'];
    }

    private static function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
