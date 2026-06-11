<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ZipArchive;

class InvoiceXmlExporter
{
    public static function make(Collection $invoices): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'invoice-xml-export-');
        $zip = new ZipArchive();
        $zip->open($temp, ZipArchive::OVERWRITE);

        $settings = self::settings();

        foreach ($invoices as $invoice) {
            $zip->addFromString(self::filename($invoice, $settings), self::xml($invoice, $settings));
        }

        $zip->close();
        $content = file_get_contents($temp);
        unlink($temp);

        return $content;
    }

    private static function xml(Invoice $invoice, array $settings): string
    {
        $invoice->loadMissing('patient');

        $document = new \DOMDocument('1.0', 'utf-8');
        $document->formatOutput = true;

        $root = $document->createElement('FatturaElettronica');
        $root->setAttribute('versione', $settings['invoice_transmission_format']);
        $root->setAttribute('xmlns', 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2');
        $document->appendChild($root);

        $header = self::append($document, $root, 'FatturaElettronicaHeader');
        $transmission = self::append($document, $header, 'DatiTrasmissione');
        $transmitter = self::append($document, $transmission, 'IdTrasmittente');
        self::append($document, $transmitter, 'IdPaese', $settings['invoice_transmitter_country_id']);
        self::append($document, $transmitter, 'IdCodice', $settings['invoice_transmitter_vat_number']);
        self::append($document, $transmission, 'ProgressivoInvio', self::progressive($invoice));
        self::append($document, $transmission, 'FormatoTrasmissione', $settings['invoice_transmission_format']);
        self::append($document, $transmission, 'CodiceDestinatario', self::recipientCode($invoice));

        $seller = self::append($document, $header, 'CedentePrestatore');
        $sellerData = self::append($document, $seller, 'DatiAnagrafici');
        $sellerVat = self::append($document, $sellerData, 'IdFiscaleIVA');
        self::append($document, $sellerVat, 'IdPaese', $settings['invoice_sender_vat_country']);
        self::append($document, $sellerVat, 'IdCodice', $settings['invoice_sender_vat_number']);
        self::append($document, $sellerData, 'CodiceFiscale', $settings['invoice_sender_tax_code']);
        $sellerName = self::append($document, $sellerData, 'Anagrafica');
        self::append($document, $sellerName, 'Denominazione', $settings['invoice_sender_name']);
        self::append($document, $sellerData, 'RegimeFiscale', $settings['invoice_tax_regime']);
        self::address($document, $seller, [
            'address' => $settings['invoice_sender_address'],
            'postal_code' => $settings['invoice_sender_postal_code'],
            'city' => $settings['invoice_sender_city'],
            'province' => $settings['invoice_sender_province'],
            'country' => $settings['invoice_sender_country'],
        ]);
        $contacts = self::append($document, $seller, 'Contatti');
        self::append($document, $contacts, 'Email', $settings['invoice_sender_email']);

        self::recipient($document, $header, $invoice);

        $body = self::append($document, $root, 'FatturaElettronicaBody');
        $general = self::append($document, $body, 'DatiGenerali');
        $generalDocument = self::append($document, $general, 'DatiGeneraliDocumento');
        self::append($document, $generalDocument, 'TipoDocumento', $settings['invoice_document_type']);
        self::append($document, $generalDocument, 'Divisa', $settings['invoice_currency']);
        self::append($document, $generalDocument, 'Data', $invoice->issued_at->format('Y-m-d'));
        self::append($document, $generalDocument, 'Numero', $invoice->number ?: self::progressive($invoice));

        $amounts = self::amounts($invoice, $settings);
        if ($amounts['social_security'] > 0) {
            $cash = self::append($document, $generalDocument, 'DatiCassaPrevidenziale');
            self::append($document, $cash, 'TipoCassa', $settings['invoice_social_security_type']);
            self::append($document, $cash, 'AlCassa', self::money($amounts['social_security_rate']));
            self::append($document, $cash, 'ImportoContributoCassa', self::money($amounts['social_security']));
            self::append($document, $cash, 'ImponibileCassa', self::money($amounts['line']));
            self::append($document, $cash, 'AliquotaIVA', self::money(0));
            self::append($document, $cash, 'Natura', $settings['invoice_vat_nature']);
        }

        self::append($document, $generalDocument, 'ImportoTotaleDocumento', self::money($amounts['total']));
        self::append($document, $generalDocument, 'Causale', $settings['invoice_default_causale']);

        $goods = self::append($document, $body, 'DatiBeniServizi');
        $line = self::append($document, $goods, 'DettaglioLinee');
        self::append($document, $line, 'NumeroLinea', '1');
        self::append($document, $line, 'Descrizione', $invoice->service ?: 'Seduta di manipolazione osteopatica');
        self::append($document, $line, 'Quantita', self::money(1));
        self::append($document, $line, 'UnitaMisura', 'PZ');
        self::append($document, $line, 'PrezzoUnitario', self::money($amounts['line']));
        self::append($document, $line, 'PrezzoTotale', self::money($amounts['line']));
        self::append($document, $line, 'AliquotaIVA', self::money(0));
        self::append($document, $line, 'Natura', $settings['invoice_vat_nature']);

        $summary = self::append($document, $goods, 'DatiRiepilogo');
        self::append($document, $summary, 'AliquotaIVA', self::money(0));
        self::append($document, $summary, 'Natura', $settings['invoice_vat_nature']);
        self::append($document, $summary, 'ImponibileImporto', self::money($amounts['taxable']));
        self::append($document, $summary, 'Imposta', self::money(0));
        self::append($document, $summary, 'RiferimentoNormativo', $settings['invoice_vat_reference']);

        $payment = self::append($document, $body, 'DatiPagamento');
        self::append($document, $payment, 'CondizioniPagamento', $settings['invoice_payment_terms']);
        $paymentDetail = self::append($document, $payment, 'DettaglioPagamento');
        self::append($document, $paymentDetail, 'ModalitaPagamento', $invoice->payment_method ?: $settings['invoice_payment_method']);
        self::append($document, $paymentDetail, 'DataScadenzaPagamento', ($invoice->payment_date ?: $invoice->issued_at)->format('Y-m-d'));
        self::append($document, $paymentDetail, 'ImportoPagamento', self::money($amounts['total']));

        return $document->saveXML();
    }

    private static function recipient(\DOMDocument $document, \DOMElement $header, Invoice $invoice): void
    {
        $patient = $invoice->patient;
        $recipient = self::append($document, $header, 'CessionarioCommittente');
        $data = self::append($document, $recipient, 'DatiAnagrafici');

        if (filled($patient->vat_number)) {
            $vat = self::append($document, $data, 'IdFiscaleIVA');
            self::append($document, $vat, 'IdPaese', $patient->country_id ?: 'IT');
            self::append($document, $vat, 'IdCodice', $patient->vat_number);
        }

        if (filled($patient->fiscal_code)) {
            self::append($document, $data, 'CodiceFiscale', $patient->fiscal_code);
        }

        $name = self::append($document, $data, 'Anagrafica');
        if (filled($patient->business_name)) {
            self::append($document, $name, 'Denominazione', $patient->business_name);
        } else {
            self::append($document, $name, 'Nome', $patient->first_name ?: 'Senza nome');
            self::append($document, $name, 'Cognome', $patient->last_name ?: 'Senza cognome');
        }

        self::address($document, $recipient, [
            'address' => trim($patient->address ?: 'Indirizzo non inserito'),
            'street_number' => $patient->street_number,
            'postal_code' => $patient->postal_code ?: '00000',
            'city' => $patient->city ?: 'Comune non inserito',
            'province' => $patient->province,
            'country' => $patient->country_id ?: 'IT',
        ]);
    }

    private static function address(\DOMDocument $document, \DOMElement $parent, array $data): void
    {
        $address = self::append($document, $parent, 'Sede');
        self::append($document, $address, 'Indirizzo', $data['address'] ?? 'Indirizzo non inserito');
        if (filled($data['street_number'] ?? null)) {
            self::append($document, $address, 'NumeroCivico', $data['street_number']);
        }
        self::append($document, $address, 'CAP', $data['postal_code'] ?? '00000');
        self::append($document, $address, 'Comune', $data['city'] ?? 'Comune non inserito');
        if (filled($data['province'] ?? null)) {
            self::append($document, $address, 'Provincia', $data['province']);
        }
        self::append($document, $address, 'Nazione', $data['country'] ?? 'IT');
    }

    private static function append(\DOMDocument $document, \DOMElement $parent, string $name, ?string $value = null): \DOMElement
    {
        $element = $document->createElement($name);
        if ($value !== null) {
            $element->appendChild($document->createTextNode($value));
        }
        $parent->appendChild($element);

        return $element;
    }

    private static function amounts(Invoice $invoice, array $settings): array
    {
        $total = (float) $invoice->amount;
        $rate = (float) $settings['invoice_social_security_rate'];
        $line = $rate > 0 ? round($total / (1 + ($rate / 100)), 2) : $total;
        $socialSecurity = round($total - $line, 2);

        return [
            'line' => $line,
            'social_security' => $socialSecurity,
            'social_security_rate' => $rate,
            'taxable' => $line + $socialSecurity,
            'total' => $total,
        ];
    }

    private static function settings(): array
    {
        $defaults = [
            'invoice_transmission_format' => 'FPR12',
            'invoice_document_type' => 'TD01',
            'invoice_currency' => 'EUR',
            'invoice_default_recipient_code' => '0000000',
            'invoice_transmitter_country_id' => 'IT',
            'invoice_transmitter_vat_number' => '01879020517',
            'invoice_sender_vat_country' => 'IT',
            'invoice_sender_vat_number' => '02429900414',
            'invoice_sender_tax_code' => 'FLPDNL85R01D488C',
            'invoice_sender_name' => 'Filipponi Danilo',
            'invoice_sender_address' => 'via Madonna Ponte 33',
            'invoice_sender_postal_code' => '61032',
            'invoice_sender_city' => 'Fano',
            'invoice_sender_province' => 'PU',
            'invoice_sender_country' => 'IT',
            'invoice_sender_email' => 'danilo.filipponi@gmail.com',
            'invoice_tax_regime' => 'RF19',
            'invoice_vat_nature' => 'N2.2',
            'invoice_vat_reference' => 'Non soggette - altri casi',
            'invoice_social_security_type' => 'TC22',
            'invoice_social_security_rate' => '4.00',
            'invoice_payment_method' => 'MP08',
            'invoice_payment_terms' => 'TP02',
            'invoice_default_causale' => 'Operazione non soggetta a ritenuta alla fonte a titolo di acconto ai sensi dell\'articolo 1, comma 67, l. n. 190 del 2014 e successive modificazioni',
        ];

        return collect($defaults)
            ->mapWithKeys(fn (string $default, string $key) => [$key => Setting::getValue($key, $default)])
            ->all();
    }

    private static function filename(Invoice $invoice, array $settings): string
    {
        $number = Str::of($invoice->number ?: (string) $invoice->id)->replaceMatches('/[^A-Za-z0-9_-]+/', '-')->trim('-');

        return 'IT'.$settings['invoice_sender_vat_number'].'_'.$number.'.xml';
    }

    private static function recipientCode(Invoice $invoice): string
    {
        return $invoice->patient?->telematic_address ?: Setting::getValue('invoice_default_recipient_code', '0000000');
    }

    private static function progressive(Invoice $invoice): string
    {
        return (string) ($invoice->progressive_number ?: $invoice->id);
    }

    private static function money(float|int $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
