<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Patient;

class InvoiceCourtesyPdf
{
    private const PAGE_W = 841.89;
    private const PAGE_H = 595.28;

    private array $commands = [];
    private int $imageWidth = 0;
    private int $imageHeight = 0;
    private ?string $imageData = null;
    private int $signatureWidth = 0;
    private int $signatureHeight = 0;
    private ?string $signatureData = null;

    public static function make(Patient $patient, Invoice $invoice, array $settings, array $amounts, array $paymentMethods): string
    {
        $pdf = new self();
        $pdf->loadLogo();
        $pdf->loadSignature();
        $pdf->draw($patient, $invoice, $settings, $amounts, $paymentMethods);

        return $pdf->document();
    }

    private function loadLogo(): void
    {
        $path = public_path('images/logo-filipponi.png');

        [$this->imageData, $this->imageWidth, $this->imageHeight] = $this->pngAsJpeg($path);
    }

    private function loadSignature(): void
    {
        $path = storage_path('app/private/firma-filipponi-danilo.png');

        [$this->signatureData, $this->signatureWidth, $this->signatureHeight] = $this->pngAsJpeg($path);
    }

    private function pngAsJpeg(string $path): array
    {
        if (! function_exists('imagecreatefrompng') || ! is_file($path)) {
            return [null, 0, 0];
        }

        $image = @imagecreatefrompng($path);
        if (! $image) {
            return [null, 0, 0];
        }

        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        ob_start();
        imagejpeg($canvas, null, 90);
        $data = ob_get_clean() ?: null;
        $width = imagesx($image);
        $height = imagesy($image);

        imagedestroy($image);
        imagedestroy($canvas);

        return [$data, $width, $height];
    }

    private function draw(Patient $patient, Invoice $invoice, array $settings, array $amounts, array $paymentMethods): void
    {
        $this->line(420.95, 18, 420.95, 577, '0.7 w [3 3] 0 d 0.62 0.68 0.67 RG');
        $this->raw('[] 0 d');

        $this->copy(22, 32, 'COPIA CLIENTE', $patient, $invoice, $settings, $amounts, $paymentMethods);
        $this->copy(442, 32, 'COPIA STUDIO', $patient, $invoice, $settings, $amounts, $paymentMethods);
    }

    private function copy(float $x, float $y, string $label, Patient $patient, Invoice $invoice, array $settings, array $amounts, array $paymentMethods): void
    {
        $w = 378;
        $payment = $invoice->payment_method && isset($paymentMethods[$invoice->payment_method])
            ? trim(str_replace($invoice->payment_method.' - ', '', $paymentMethods[$invoice->payment_method]))
            : 'Non indicato';
        $status = [
            'draft' => 'Bozza',
            'sent' => 'Emessa',
            'paid' => 'Pagata',
            'cancelled' => 'Annullata',
        ][$invoice->status] ?? $invoice->status;
        $patientAddress = trim(collect([$patient->address, $patient->street_number])->filter()->join(' '));
        $patientCity = collect([$patient->postal_code, $patient->city, $patient->province])->filter()->join(' ');

        $this->rect($x, $y, $w, 525, '0.78 0.84 0.83 RG 0.35 w');
        $this->imageFit($x + 16, $y + 18, 78, 48);
        $rightEdge = $x + $w - 24;
        $this->textRightFit($rightEdge, $y + 24, $label, 120, 7, true);
        $this->textRightFit($rightEdge, $y + 40, 'FATTURA', 120, 12, true);
        $this->textRightFit($rightEdge, $y + 56, (string) $invoice->number, 120, 12, true);
        $this->textRightFit($rightEdge, $y + 72, $invoice->issued_at->format('d/m/Y'), 120, 9, true);

        $this->line($x + 18, $y + 112, $x + $w - 18, $y + 112);
        $this->text($x + 18, $y + 128, 'CEDENTE / PRESTATORE', 7, true);
        $this->textFit($x + 18, $y + 144, (string) $settings['invoice_sender_name'], 150, 9, true);
        $this->textFit($x + 18, $y + 158, (string) $settings['invoice_sender_address'], 150, 7);
        $this->textFit($x + 18, $y + 170, trim($settings['invoice_sender_postal_code'].' '.$settings['invoice_sender_city'].' '.$settings['invoice_sender_province']), 150, 7);
        $this->textFit($x + 18, $y + 182, 'P.IVA '.$settings['invoice_sender_vat_number'], 150, 7);
        $this->textFit($x + 18, $y + 194, 'CF '.$settings['invoice_sender_tax_code'], 150, 7);

        $this->text($x + 205, $y + 128, 'CLIENTE', 7, true);
        $this->textFit($x + 205, $y + 144, $patient->full_name, 155, 9, true);
        $this->textFit($x + 205, $y + 158, $patientAddress ?: 'Indirizzo non inserito', 155, 7);
        $this->textFit($x + 205, $y + 170, $patientCity ?: 'Comune non inserito', 155, 7);
        $this->textFit($x + 205, $y + 182, 'CF '.($patient->fiscal_code ?: 'Non inserito'), 155, 7);

        $this->line($x + 18, $y + 226, $x + $w - 18, $y + 226);
        $this->text($x + 18, $y + 242, 'DESCRIZIONE', 7, true);
        $this->text($x + 290, $y + 242, 'QUANTITA', 7, true, 'R');
        $this->text($x + $w - 20, $y + 242, 'IMPORTO', 7, true, 'R');
        $this->line($x + 18, $y + 252, $x + $w - 18, $y + 252);
        $this->textFit($x + 18, $y + 270, (string) $invoice->service, 245, 7, true);
        $this->text($x + 18, $y + 284, 'QUANTITA: '.number_format($amounts['quantity'], 0, ',', '.').' - PREZZO UNITARIO € '.number_format($amounts['unit'], 2, ',', '.'), 6.5, true);
        $this->text($x + 290, $y + 270, number_format($amounts['quantity'], 0, ',', '.'), 7, false, 'R');
        $this->text($x + $w - 20, $y + 270, '€ '.number_format($amounts['line'], 2, ',', '.'), 7, false, 'R');

        $this->line($x + 18, $y + 304, $x + $w - 18, $y + 304);
        $this->text($x + 18, $y + 322, 'Pagamento: '.$payment, 7);
        $this->text($x + 18, $y + 336, 'Data pagamento: '.($invoice->payment_date ?: $invoice->issued_at)->format('d/m/Y'), 7);
        $this->text($x + 18, $y + 350, 'Stato: '.$status, 7);

        $tx = $x + 255;
        $this->text($tx, $y + 322, 'I.N.P.S. '.number_format($amounts['social_security_rate'], 0, ',', '.').'%', 7);
        $this->text($x + $w - 20, $y + 322, '€ '.number_format($amounts['social_security'], 2, ',', '.'), 7, true, 'R');
        $this->text($tx, $y + 338, 'IVA '.number_format($amounts['vat_rate'], 2, ',', '.').'% '.$amounts['vat_nature'], 7);
        $this->text($x + $w - 20, $y + 338, '€ '.number_format($amounts['vat'], 2, ',', '.'), 7, true, 'R');
        $this->text($tx, $y + 354, 'Bollo', 7);
        $this->text($x + $w - 20, $y + 354, '€ '.number_format($amounts['stamp'], 2, ',', '.'), 7, true, 'R');
        $this->line($tx, $y + 363, $x + $w - 20, $y + 363);
        $this->text($tx, $y + 384, 'Totale', 13, true);
        $this->text($x + $w - 20, $y + 384, 'EUR '.number_format($amounts['total'], 2, ',', '.'), 13, true, 'R');

        $this->line($x + 18, $y + 420, $x + $w - 18, $y + 420);
        $legal = [
            'Regime fiscale forfettario ex art.1, commi 54 e segg., della Legge n. 190/2014 così come modificato dalla Legge n.',
            '208/2015 e dalla Legge n. 145/2018.',
            'Operazione in franchigia da IVA-non soggetta a ritenuta d\'acconto.',
        ];
        $this->textWrap($x + 18, $y + 438, implode(' ', $legal), $w - 36, 5.8, 9);
        $this->text($x + 18, $y + 478, 'COPIA DI CORTESIA', 9, true);
        $this->text($x + $w - 82, $y + 456, 'Firma', 7, true);
        $this->signatureFit($x + $w - 118, $y + 464, 98, 36);
    }

    private function document(): string
    {
        $content = implode("\n", $this->commands);
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::PAGE_W.' '.self::PAGE_H.'] /Resources << /Font << /F1 5 0 R /F2 6 0 R >> /XObject << /Logo 7 0 R /Signature 8 0 R >> >> /Contents 4 0 R >>',
            "<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            $this->imageObject($this->imageData, $this->imageWidth, $this->imageHeight),
            $this->imageObject($this->signatureData, $this->signatureWidth, $this->signatureHeight),
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    }

    private function imageObject(?string $data, int $width, int $height): string
    {
        if (! $data) {
            return '<< /Type /XObject /Subtype /Image /Width 1 /Height 1 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Length 3 >>'."\nstream\n".str_repeat(chr(255), 3)."\nendstream";
        }

        return '<< /Type /XObject /Subtype /Image /Width '.$width.' /Height '.$height.' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($data)." >>\nstream\n".$data."\nendstream";
    }

    private function text(float $x, float $y, string $text, float $size = 8, bool $bold = false, string $align = 'L'): void
    {
        $encoded = $this->encode($text);

        if ($align === 'R') {
            $x -= $this->textWidth($text, $size, $bold);

            $this->raw('BT /'.($bold ? 'F2' : 'F1').' '.$size.' Tf 0 0 0 rg '.$this->n($x).' '.$this->n(self::PAGE_H - $y).' Td ('.$this->escape($encoded).') Tj ET');

            return;
        }

        $this->raw('BT /'.($bold ? 'F2' : 'F1').' '.$size.' Tf 0 0 0 rg '.$this->n($x).' '.$this->n(self::PAGE_H - $y).' Td ('.$this->escape($encoded).') Tj ET');
    }

    private function textFit(float $x, float $y, string $text, float $maxW, float $size = 8, bool $bold = false): void
    {
        $fittedSize = $size;
        $text = trim($text);

        while ($this->textWidth($text, $fittedSize) > $maxW && $fittedSize > 5.2) {
            $fittedSize -= 0.2;
        }

        while ($this->textWidth($text, $fittedSize) > $maxW && strlen($text) > 4) {
            $text = rtrim(substr($text, 0, -4)).'...';
        }

        $this->text($x, $y, $text, $fittedSize, $bold);
    }

    private function textRightFit(float $rightEdge, float $y, string $text, float $maxW, float $size = 8, bool $bold = false): void
    {
        $fittedSize = $size;
        $text = trim($text);

        while ($this->textWidth($text, $fittedSize, $bold) > $maxW && $fittedSize > 5.2) {
            $fittedSize -= 0.2;
        }

        $x = $rightEdge - min($this->textWidth($text, $fittedSize, $bold), $maxW);
        $this->text($x, $y, $text, $fittedSize, $bold);
    }

    private function textWrap(float $x, float $y, string $text, float $maxW, float $size = 8, float $lineHeight = 10): void
    {
        $line = '';
        $row = 0;

        foreach (preg_split('/\s+/', trim($text)) as $word) {
            $candidate = trim($line.' '.$word);

            if ($line !== '' && $this->textWidth($candidate, $size) > $maxW) {
                $this->text($x, $y + ($row * $lineHeight), $line, $size);
                $line = $word;
                $row++;

                continue;
            }

            $line = $candidate;
        }

        if ($line !== '') {
            $this->text($x, $y + ($row * $lineHeight), $line, $size);
        }
    }

    private function textWidth(string $text, float $size, bool $bold = false): float
    {
        return strlen($this->encode($text)) * $size * ($bold ? 0.54 : 0.50);
    }

    private function line(float $x1, float $y1, float $x2, float $y2, string $style = '0.45 w 0.78 0.84 0.83 RG'): void
    {
        $this->raw($style.' '.$this->n($x1).' '.$this->n(self::PAGE_H - $y1).' m '.$this->n($x2).' '.$this->n(self::PAGE_H - $y2).' l S');
    }

    private function rect(float $x, float $y, float $w, float $h, string $style): void
    {
        $this->raw($style.' '.$this->n($x).' '.$this->n(self::PAGE_H - $y - $h).' '.$this->n($w).' '.$this->n($h).' re S');
    }

    private function image(float $x, float $y, float $w, float $h): void
    {
        $this->drawImage('Logo', $x, $y, $w, $h);
    }

    private function drawImage(string $name, float $x, float $y, float $w, float $h): void
    {
        $this->raw('q '.$this->n($w).' 0 0 '.$this->n($h).' '.$this->n($x).' '.$this->n(self::PAGE_H - $y - $h).' cm /'.$name.' Do Q');
    }

    private function imageFit(float $x, float $y, float $maxW, float $maxH): void
    {
        if ($this->imageWidth <= 0 || $this->imageHeight <= 0) {
            $this->image($x, $y, $maxW, $maxH);

            return;
        }

        $ratio = min($maxW / $this->imageWidth, $maxH / $this->imageHeight);
        $w = $this->imageWidth * $ratio;
        $h = $this->imageHeight * $ratio;

        $this->image($x, $y + (($maxH - $h) / 2), $w, $h);
    }

    private function signatureFit(float $x, float $y, float $maxW, float $maxH): void
    {
        if ($this->signatureWidth <= 0 || $this->signatureHeight <= 0) {
            return;
        }

        $ratio = min($maxW / $this->signatureWidth, $maxH / $this->signatureHeight);
        $w = $this->signatureWidth * $ratio;
        $h = $this->signatureHeight * $ratio;

        $this->drawImage('Signature', $x + (($maxW - $w) / 2), $y + (($maxH - $h) / 2), $w, $h);
    }

    private function raw(string $command): void
    {
        $this->commands[] = $command;
    }

    private function encode(string $text): string
    {
        return iconv('UTF-8', 'Windows-1252//TRANSLIT', $text) ?: $text;
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function n(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}

