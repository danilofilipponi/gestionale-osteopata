<?php

namespace App\Support;

use App\Models\Patient;
use App\Models\PrivacyConsent;

class PrivacyConsentPdf
{
    private const PAGE_W = 595.28;
    private const PAGE_H = 841.89;
    private const MARGIN = 42;

    private array $pages = [];
    private ?string $signatureData = null;
    private int $signatureWidth = 1;
    private int $signatureHeight = 1;

    public static function make(Patient $patient, ?PrivacyConsent $consent): string
    {
        $pdf = new self();
        $pdf->loadSignature($consent?->signature_data);
        $pdf->draw($patient, $consent);

        return $pdf->document();
    }

    private function loadSignature(?string $dataUrl): void
    {
        if (! $dataUrl || ! str_contains($dataUrl, ',')) {
            return;
        }

        [, $payload] = explode(',', $dataUrl, 2);
        $binary = base64_decode($payload, true);

        if (! $binary || ! function_exists('imagecreatefromstring')) {
            return;
        }

        $image = @imagecreatefromstring($binary);
        if (! $image) {
            return;
        }

        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        ob_start();
        imagejpeg($canvas, null, 90);
        $this->signatureData = ob_get_clean() ?: null;
        $this->signatureWidth = imagesx($image);
        $this->signatureHeight = imagesy($image);

        imagedestroy($image);
        imagedestroy($canvas);
    }

    private function draw(Patient $patient, ?PrivacyConsent $consent): void
    {
        $this->newPage();

        $x = self::MARGIN;
        $y = self::MARGIN;
        $w = self::PAGE_W - (self::MARGIN * 2);
        $signedAt = ($consent?->signed_at ?: now())->format('d/m/Y');
        $residence = trim(collect([
            trim(collect([$patient->address, $patient->street_number])->filter()->join(' ')),
            $patient->postal_code,
            $patient->city,
            $patient->province ? '('.$patient->province.')' : null,
        ])->filter()->join(' '));

        $cursor = $this->textWrap($x, $y, 'CONSENSO INFORMATO AL TRATTAMENTO OSTEOPATICO E INFORMATIVA PRIVACY GDPR', $w, 11.2, 14, true);
        $this->text($x, $cursor + 10, 'OSTEOPATA: DANILO FILIPPONI', 10, true);
        $this->text($x, $cursor + 25, 'Sede legale: Via Madonna Ponte 33 - 61032 Fano (PU)', 8.5);
        $this->text($x, $cursor + 39, 'Codice fiscale: FLPDNL85R01D488C    Partita IVA: 02429900414', 8.5);
        $this->text($x, $cursor + 56, 'Sedi operative:', 8.5, true);
        $this->text($x + 14, $cursor + 70, '- Via Carlo Gozzi n. 8 - 61032 Fano (PU)', 8.5);
        $this->text($x + 14, $cursor + 84, '- Via Purgotti n. 19 - 61043 Cagli (PU)', 8.5);
        $this->text($x, $cursor + 101, 'Telefono: 3202181376', 8.5);
        $this->text($x, $cursor + 115, 'E-mail: info@osteopatafilipponi.it', 8.5);
        $this->line($x, $cursor + 133, $x + $w, $cursor + 133);

        $cursor += 158;
        $this->section($x, $cursor, 'DATI DEL PAZIENTE');
        $cursor += 22;
        $this->text($x, $cursor, 'Nome e Cognome: '.$patient->full_name, 9, true);
        $cursor += 16;
        $this->text($x, $cursor, 'Nato/a a: '.($patient->birth_place ?: 'Non inserito').'    il: '.($patient->birth_date?->format('d/m/Y') ?: 'Non inserita'), 8.5);
        $cursor += 16;
        $this->text($x, $cursor, 'Codice Fiscale: '.($patient->fiscal_code ?: 'Non inserito'), 8.5);
        $cursor += 16;
        $cursor = $this->textWrap($x, $cursor, 'Residente in: '.($residence ?: 'Non inserita'), $w, 8.5, 12) + 14;

        $this->section($x, $cursor, 'CONSENSO INFORMATO AL TRATTAMENTO OSTEOPATICO');
        $cursor += 22;
        $this->paragraph($x, $cursor, 'Il/La sottoscritto/a dichiara:', $w);
        $cursor += 18;
        foreach ([
            '1. di essersi rivolto spontaneamente allo studio per una valutazione osteopatica;',
            '2. di aver ricevuto informazioni chiare, complete e comprensibili riguardo finalita, modalita, benefici, limiti, indicazioni, controindicazioni e possibili rischi del trattamento osteopatico;',
            "3. di essere stato informato che l'osteopatia e una professione sanitaria disciplinata dalla normativa vigente;",
            "4. di essere stato informato che il trattamento osteopatico si avvale di tecniche manuali e che potrebbe essere necessario un contatto fisico diretto e la rimozione di alcuni capi di abbigliamento;",
            '5. di comprendere che il trattamento osteopatico non sostituisce visite mediche, accertamenti diagnostici o terapie prescritte da professionisti sanitari competenti;',
            '6. di aver comunicato in maniera completa e veritiera tutte le informazioni relative al proprio stato di salute, comprese patologie, interventi chirurgici, traumi, fratture, gravidanza, terapie farmacologiche, dispositivi impiantati e altre condizioni rilevanti;',
            '7. di poter revocare il consenso in qualsiasi momento.',
        ] as $item) {
            $cursor = $this->ensureSpace($cursor, 44);
            $cursor = $this->textWrap($x, $cursor, $item, $w, 8.5, 12) + 7;
        }

        $cursor = $this->ensureSpace($cursor, 80);
        $this->section($x, $cursor + 10, 'INFORMATIVA PRIVACY AI SENSI DEL GDPR 2016/679');
        $cursor += 34;

        $privacySections = [
            ['1. TITOLARE DEL TRATTAMENTO', 'Osteopata: Danilo Filipponi. Sede legale: Via Madonna Ponte 33 - 61032 Fano (PU). Codice fiscale: FLPDNL85R01D488C. Partita IVA: 02429900414. Sedi operative: Via Carlo Gozzi n. 8 - 61032 Fano (PU); Via Purgotti n. 19 - 61043 Cagli (PU). Telefono: 3202181376. E-mail: info@osteopatafilipponi.it.'],
            ['2. FINALITA DEL TRATTAMENTO', 'I dati personali e i dati particolari relativi alla salute vengono trattati per: gestione della prestazione osteopatica; redazione e conservazione della documentazione sanitaria; adempimenti fiscali e amministrativi; tutela dei diritti del professionista e del paziente; gestione degli appuntamenti.'],
            ['3. MODALITA DI TRATTAMENTO', 'I dati sono trattati nel rispetto dei principi di liceita, correttezza, trasparenza, minimizzazione, esattezza e integrita previsti dal GDPR. I dati possono essere conservati sia in formato cartaceo sia elettronico mediante software gestionali, sistemi cloud, servizi di posta elettronica e strumenti di comunicazione professionale.'],
            ['4. CONSERVAZIONE', "I dati saranno conservati per il tempo necessario all'esecuzione delle prestazioni professionali e comunque per il periodo richiesto dagli obblighi fiscali, amministrativi e di tutela professionale previsti dalla normativa vigente."],
            ['5. DESTINATARI DEI DATI', "I dati potranno essere comunicati esclusivamente a: Autorita competenti; consulenti fiscali, amministrativi e legali; fornitori di servizi informatici e gestionali; medico curante o specialisti previa autorizzazione dell'interessato; familiari esclusivamente previo consenso dell'interessato. I dati non saranno diffusi."],
            ['6. UTILIZZO DI WHATSAPP, EMAIL E SMS', "Lo studio puo utilizzare WhatsApp, SMS ed e-mail per: promemoria appuntamenti; variazioni di orario; comunicazioni amministrative; invio di documentazione richiesta dal paziente. L'utilizzo di tali strumenti puo comportare il trattamento del numero telefonico e dell'indirizzo e-mail da parte dei rispettivi fornitori di servizi. Il paziente e consapevole che tali piattaforme operano secondo le proprie informative privacy e condizioni d'uso."],
            ['7. TRASFERIMENTO DATI', 'Qualora vengano utilizzati servizi cloud o strumenti informatici forniti da soggetti terzi, il trattamento avverra nel rispetto delle garanzie previste dal Regolamento UE 2016/679.'],
            ["8. DIRITTI DELL'INTERESSATO", "L'interessato puo esercitare i diritti previsti dagli artt. 15-22 del GDPR: accesso, rettifica, cancellazione, limitazione, opposizione, portabilita dei dati e reclamo all'Autorita Garante."],
        ];

        foreach ($privacySections as [$title, $body]) {
            $cursor = $this->ensureSpace($cursor, 78);
            $this->text($x, $cursor, $title, 8.8, true);
            $cursor = $this->textWrap($x, $cursor + 15, $body, $w, 8.2, 11) + 12;
        }

        $cursor = $this->ensureSpace($cursor, 245);
        $this->section($x, $cursor, 'CONSENSI SPECIFICI');
        $cursor += 22;

        foreach ([
            'CONSENSO AL TRATTAMENTO OSTEOPATICO',
            'COMUNICAZIONE DEI DATI AL MEDICO CURANTE E/O SPECIALISTI',
            'TRATTAMENTO DEI DATI SANITARI',
            'COMUNICAZIONE DEI DATI AI FAMILIARI',
            'COMUNICAZIONI E PROMEMORIA TRAMITE WHATSAPP',
            'COMUNICAZIONI E PROMEMORIA TRAMITE EMAIL',
            'COMUNICAZIONI E PROMEMORIA TRAMITE SMS',
        ] as $item) {
            $cursor = $this->ensureSpace($cursor, 28);
            $this->text($x, $cursor, $item, 8.4, true);
            $this->text($x + 320, $cursor, '[X] ACCONSENTO      [ ] NON ACCONSENTO', 8.4);
            $cursor += 22;
        }

        $cursor = $this->ensureSpace($cursor, 120);
        $this->line($x, $cursor, $x + $w, $cursor);
        $cursor += 25;
        $this->text($x, $cursor, 'DATA: '.$signedAt, 9, true);
        $this->text($x + 210, $cursor, 'FIRMA', 9, true);

        if ($this->signatureData) {
            $this->imageFit($x + 210, $cursor + 12, 275, 92);
        } else {
            $this->rect($x + 210, $cursor + 10, 275, 92);
        }
    }

    private function newPage(): void
    {
        $this->pages[] = [];
    }

    private function currentPageIndex(): int
    {
        return count($this->pages) - 1;
    }

    private function ensureSpace(float $cursor, float $needed): float
    {
        if ($cursor + $needed <= self::PAGE_H - self::MARGIN) {
            return $cursor;
        }

        $this->newPage();

        return self::MARGIN;
    }

    private function document(): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            5 => $this->signatureObject(),
        ];

        $kids = [];
        $nextId = 6;
        foreach ($this->pages as $commands) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $kids[] = $pageId.' 0 R';
            $content = implode("\n", $commands);
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::PAGE_W.' '.self::PAGE_H.'] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> /XObject << /Sig 5 0 R >> >> /Contents '.$contentId.' 0 R >>';
            $objects[$contentId] = "<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($kids).' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$object."\nendobj\n";
        }

        $xref = strlen($pdf);
        $size = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 ".$size."\n0000000000 65535 f \n";
        for ($i = 1; $i < $size; $i++) {
            $pdf .= str_pad((string) ($offsets[$i] ?? 0), 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        return $pdf."trailer\n<< /Size ".$size." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    }

    private function signatureObject(): string
    {
        if (! $this->signatureData) {
            return '<< /Type /XObject /Subtype /Image /Width 1 /Height 1 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Length 3 >>'."\nstream\n".str_repeat(chr(255), 3)."\nendstream";
        }

        return '<< /Type /XObject /Subtype /Image /Width '.$this->signatureWidth.' /Height '.$this->signatureHeight.' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($this->signatureData)." >>\nstream\n".$this->signatureData."\nendstream";
    }

    private function section(float $x, float $y, string $text): void
    {
        $this->text($x, $y, $text, 9, true);
        $this->line($x, $y + 6, self::PAGE_W - self::MARGIN, $y + 6);
    }

    private function paragraph(float $x, float $y, string $text, float $maxW): float
    {
        return $this->textWrap($x, $y, $text, $maxW, 8.5, 12);
    }

    private function text(float $x, float $y, string $text, float $size = 8, bool $bold = false): void
    {
        $encoded = $this->encode($text);
        $this->raw('BT /'.($bold ? 'F2' : 'F1').' '.$size.' Tf 0 0 0 rg '.$this->n($x).' '.$this->n(self::PAGE_H - $y).' Td ('.$this->escape($encoded).') Tj ET');
    }

    private function textWrap(float $x, float $y, string $text, float $maxW, float $size = 8, float $lineHeight = 10, bool $bold = false): float
    {
        $line = '';
        $row = 0;

        foreach (preg_split('/\s+/', trim($text)) as $word) {
            $candidate = trim($line.' '.$word);

            if ($line !== '' && strlen($this->encode($candidate)) * $size * 0.48 > $maxW) {
                $this->text($x, $y + ($row * $lineHeight), $line, $size, $bold);
                $line = $word;
                $row++;
                continue;
            }

            $line = $candidate;
        }

        if ($line !== '') {
            $this->text($x, $y + ($row * $lineHeight), $line, $size, $bold);
        }

        return $y + (($row + 1) * $lineHeight);
    }

    private function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->raw('0.45 w 0.72 0.82 0.80 RG '.$this->n($x1).' '.$this->n(self::PAGE_H - $y1).' m '.$this->n($x2).' '.$this->n(self::PAGE_H - $y2).' l S');
    }

    private function rect(float $x, float $y, float $w, float $h): void
    {
        $this->raw('0.45 w 0.72 0.82 0.80 RG '.$this->n($x).' '.$this->n(self::PAGE_H - $y - $h).' '.$this->n($w).' '.$this->n($h).' re S');
    }

    private function image(float $x, float $y, float $w, float $h): void
    {
        $this->raw('q '.$this->n($w).' 0 0 '.$this->n($h).' '.$this->n($x).' '.$this->n(self::PAGE_H - $y - $h).' cm /Sig Do Q');
    }

    private function imageFit(float $x, float $y, float $maxW, float $maxH): void
    {
        $ratio = min($maxW / $this->signatureWidth, $maxH / $this->signatureHeight);
        $w = $this->signatureWidth * $ratio;
        $h = $this->signatureHeight * $ratio;
        $this->image($x, $y + (($maxH - $h) / 2), $w, $h);
    }

    private function raw(string $command): void
    {
        $this->pages[$this->currentPageIndex()][] = $command;
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
