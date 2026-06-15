<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Setting;

class InvoiceDefaults
{
    public static function services(): array
    {
        $services = json_decode(Setting::getValue('invoice_services', '[]'), true) ?: [];

        if ($services !== []) {
            return $services;
        }

        return [
            [
                'name' => 'Seduta di manipolazione osteopatica',
                'description' => 'Seduta di manipolazione osteopatica',
                'amount' => 38.46,
                'vat_rate' => 0,
                'social_security_rate' => 4,
                'vat_nature' => 'N2.2',
                'unit_measure' => 'PZ',
                'stamp_duty' => true,
            ],
        ];
    }

    public static function defaultService(): array
    {
        return self::services()[0] ?? [
            'name' => 'Seduta di manipolazione osteopatica',
            'description' => 'Seduta di manipolazione osteopatica',
            'amount' => 0,
            'vat_rate' => 0,
            'social_security_rate' => (float) Setting::getValue('invoice_social_security_rate', '4.00'),
            'vat_nature' => Setting::getValue('invoice_vat_nature', 'N2.2'),
            'unit_measure' => 'PZ',
            'stamp_duty' => true,
        ];
    }

    public static function settings(): array
    {
        $defaults = [
            'invoice_currency' => 'EUR',
            'invoice_sender_name' => 'Filipponi Danilo',
            'invoice_sender_address' => 'via Madonna Ponte 33',
            'invoice_sender_postal_code' => '61032',
            'invoice_sender_city' => 'Fano',
            'invoice_sender_province' => 'PU',
            'invoice_sender_country' => 'IT',
            'invoice_sender_vat_number' => '02429900414',
            'invoice_sender_tax_code' => 'FLPDNL85R01D488C',
            'invoice_vat_nature' => 'N2.2',
            'invoice_vat_reference' => 'Non soggette - altri casi',
            'invoice_social_security_rate' => '4.00',
            'invoice_payment_method' => 'MP08',
            'invoice_payment_terms' => 'TP02',
            'invoice_stamp_threshold' => '77.47',
            'invoice_stamp_amount' => '2.00',
            'invoice_default_causale' => 'Operazione non soggetta a ritenuta alla fonte',
        ];

        return collect($defaults)
            ->mapWithKeys(fn (string $default, string $key) => [$key => Setting::getValue($key, $default)])
            ->all();
    }

    public static function paymentMethods(): array
    {
        return [
            'MP01' => 'MP01 - Contanti',
            'MP02' => 'MP02 - Assegno',
            'MP03' => 'MP03 - Assegno circolare',
            'MP04' => 'MP04 - Contanti presso Tesoreria',
            'MP05' => 'MP05 - Bonifico',
            'MP06' => 'MP06 - Vaglia cambiario',
            'MP07' => 'MP07 - Bollettino bancario',
            'MP08' => 'MP08 - Carta di pagamento',
            'MP09' => 'MP09 - RID',
            'MP10' => 'MP10 - RID utenze',
            'MP11' => 'MP11 - RID veloce',
            'MP12' => 'MP12 - RIBA',
            'MP13' => 'MP13 - MAV',
            'MP14' => 'MP14 - Quietanza erario',
            'MP15' => 'MP15 - Giroconto su conti di contabilita speciale',
            'MP16' => 'MP16 - Domiciliazione bancaria',
            'MP17' => 'MP17 - Domiciliazione postale',
            'MP18' => 'MP18 - Bollettino di c/c postale',
            'MP19' => 'MP19 - SEPA Direct Debit',
            'MP20' => 'MP20 - SEPA Direct Debit CORE',
            'MP21' => 'MP21 - SEPA Direct Debit B2B',
            'MP22' => 'MP22 - Trattenuta su somme gia riscosse',
            'MP23' => 'MP23 - PagoPA',
        ];
    }

    public static function nextNumber(?int $year = null): array
    {
        $year ??= (int) now()->format('Y');
        $progressive = ((int) Invoice::where('year', $year)->max('progressive_number')) + 1;

        return [
            'year' => $year,
            'progressive_number' => $progressive,
            'number' => $progressive.'/'.$year,
        ];
    }

    public static function amounts(Invoice $invoice): array
    {
        $settings = self::settings();
        $service = collect(self::services())
            ->first(fn (array $service) => ($service['name'] ?? '') === $invoice->service)
            ?? self::defaultService();

        $quantity = max((float) ($invoice->quantity ?: 1), 1);
        $parsed = self::parseTechnicalDescription((string) $invoice->description);
        $storedTotal = (float) $invoice->amount;
        $line = (float) ($invoice->line_amount ?? $parsed['line'] ?? $storedTotal);
        $socialSecurityRate = (float) ($service['social_security_rate'] ?? $settings['invoice_social_security_rate']);
        $vatRate = (float) ($service['vat_rate'] ?? 0);
        $hasSeparateLine = $invoice->line_amount !== null || array_key_exists('line', $parsed);
        $alreadyGrossTotal = $hasSeparateLine && $storedTotal > 0 && $line >= $storedTotal;

        if ($alreadyGrossTotal) {
            $stamp = $parsed['stamp'] ?? 0.0;
            $grossWithoutStamp = max($storedTotal - $stamp, 0);
            $socialSecurity = $parsed['social_security'] ?? ($socialSecurityRate > 0
                ? round($grossWithoutStamp - ($grossWithoutStamp / (1 + ($socialSecurityRate / 100))), 2)
                : 0.0);
            $line = max(round($grossWithoutStamp - $socialSecurity, 2), 0);
            $taxable = $line + $socialSecurity;
            $vat = 0.0;
            $total = $storedTotal;
        } else {
            $socialSecurity = $parsed['social_security'] ?? ($line * $socialSecurityRate / 100);
            $taxable = $line + $socialSecurity;
            $vat = $taxable * $vatRate / 100;
            $stamp = $parsed['stamp'] ?? ((bool) ($service['stamp_duty'] ?? false) && $taxable > (float) $settings['invoice_stamp_threshold']
                ? (float) $settings['invoice_stamp_amount']
                : 0.0);
            $calculatedTotal = $line + $socialSecurity + $vat + $stamp;
            $total = $hasSeparateLine ? $calculatedTotal : $storedTotal;
        }
        $unit = $line / $quantity;

        return [
            'line' => $line,
            'unit' => $unit,
            'quantity' => $quantity,
            'social_security_rate' => $socialSecurityRate,
            'social_security' => $socialSecurity,
            'vat_rate' => $vatRate,
            'vat' => $vat,
            'stamp' => $stamp,
            'taxable' => $taxable,
            'total' => $total,
            'vat_nature' => $service['vat_nature'] ?? $settings['invoice_vat_nature'],
            'vat_reference' => $settings['invoice_vat_reference'],
        ];
    }

    private static function parseTechnicalDescription(string $description): array
    {
        $values = [];

        foreach ([
            'line' => 'Importo',
            'social_security' => 'Inps',
            'stamp' => 'Bollo',
        ] as $key => $label) {
            if (preg_match('/'.$label.':\s*([0-9]+(?:[,.][0-9]+)?)/i', $description, $matches)) {
                $values[$key] = (float) str_replace(',', '.', $matches[1]);
            }
        }

        return $values;
    }
}
