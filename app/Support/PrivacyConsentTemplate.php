<?php

namespace App\Support;

use App\Models\Patient;
use App\Models\Setting;

class PrivacyConsentTemplate
{
    public static function text(): string
    {
        return Setting::getValue('privacy_consent_template', self::defaultText()) ?: self::defaultText();
    }

    public static function compile(Patient $patient, string $signedAt): string
    {
        $residence = trim(collect([
            trim(collect([$patient->address, $patient->street_number])->filter()->join(' ')),
            $patient->postal_code,
            $patient->city,
            $patient->province ? '('.$patient->province.')' : null,
        ])->filter()->join(' '));

        $replacements = [
            '{{paziente_nome_cognome}}' => $patient->full_name ?: 'Non inserito',
            '{{paziente_luogo_nascita}}' => $patient->birth_place ?: 'Non inserito',
            '{{paziente_data_nascita}}' => $patient->birth_date?->format('d/m/Y') ?: 'Non inserita',
            '{{paziente_codice_fiscale}}' => $patient->fiscal_code ?: 'Non inserito',
            '{{paziente_residenza}}' => $residence ?: 'Non inserita',
            '{{data_consenso}}' => $signedAt,
        ];

        return strtr(self::text(), $replacements);
    }

    public static function defaultText(): string
    {
        return <<<'TEXT'
CONSENSO INFORMATO AL TRATTAMENTO OSTEOPATICO E INFORMATIVA PRIVACY GDPR

OSTEOPATA: DANILO FILIPPONI
Sede legale: Via Madonna Ponte 33 - 61032 Fano (PU)
Codice fiscale: FLPDNL85R01D488C    Partita IVA: 02429900414
Sedi operative:
- Via Carlo Gozzi n. 8 - 61032 Fano (PU)
- Via Purgotti n. 19 - 61043 Cagli (PU)
Telefono: 3202181376
E-mail: info@osteopatafilipponi.it

DATI DEL PAZIENTE
Nome e Cognome: {{paziente_nome_cognome}}
Nato/a a: {{paziente_luogo_nascita}}    il: {{paziente_data_nascita}}
Codice Fiscale: {{paziente_codice_fiscale}}
Residente in: {{paziente_residenza}}

CONSENSO INFORMATO AL TRATTAMENTO OSTEOPATICO
Il/La sottoscritto/a dichiara:
1. di essersi rivolto spontaneamente allo studio per una valutazione osteopatica;
2. di aver ricevuto informazioni chiare, complete e comprensibili riguardo finalita, modalita, benefici, limiti, indicazioni, controindicazioni e possibili rischi del trattamento osteopatico;
3. di essere stato informato che l'osteopatia e una professione sanitaria disciplinata dalla normativa vigente;
4. di essere stato informato che il trattamento osteopatico si avvale di tecniche manuali e che potrebbe essere necessario un contatto fisico diretto e la rimozione di alcuni capi di abbigliamento;
5. di comprendere che il trattamento osteopatico non sostituisce visite mediche, accertamenti diagnostici o terapie prescritte da professionisti sanitari competenti;
6. di aver comunicato in maniera completa e veritiera tutte le informazioni relative al proprio stato di salute, comprese patologie, interventi chirurgici, traumi, fratture, gravidanza, terapie farmacologiche, dispositivi impiantati e altre condizioni rilevanti;
7. di poter revocare il consenso in qualsiasi momento.

INFORMATIVA PRIVACY AI SENSI DEL GDPR 2016/679

1. TITOLARE DEL TRATTAMENTO
Osteopata: Danilo Filipponi. Sede legale: Via Madonna Ponte 33 - 61032 Fano (PU). Codice fiscale: FLPDNL85R01D488C. Partita IVA: 02429900414. Sedi operative: Via Carlo Gozzi n. 8 - 61032 Fano (PU); Via Purgotti n. 19 - 61043 Cagli (PU). Telefono: 3202181376. E-mail: info@osteopatafilipponi.it.

2. FINALITA DEL TRATTAMENTO
I dati personali e i dati particolari relativi alla salute vengono trattati per: gestione della prestazione osteopatica; redazione e conservazione della documentazione sanitaria; adempimenti fiscali e amministrativi; tutela dei diritti del professionista e del paziente; gestione degli appuntamenti.

3. MODALITA DI TRATTAMENTO
I dati sono trattati nel rispetto dei principi di liceita, correttezza, trasparenza, minimizzazione, esattezza e integrita previsti dal GDPR. I dati possono essere conservati sia in formato cartaceo sia elettronico mediante software gestionali, sistemi cloud, servizi di posta elettronica e strumenti di comunicazione professionale.

4. CONSERVAZIONE
I dati saranno conservati per il tempo necessario all'esecuzione delle prestazioni professionali e comunque per il periodo richiesto dagli obblighi fiscali, amministrativi e di tutela professionale previsti dalla normativa vigente.

5. DESTINATARI DEI DATI
I dati potranno essere comunicati esclusivamente a: Autorita competenti; consulenti fiscali, amministrativi e legali; fornitori di servizi informatici e gestionali; medico curante o specialisti previa autorizzazione dell'interessato; familiari esclusivamente previo consenso dell'interessato. I dati non saranno diffusi.

6. UTILIZZO DI WHATSAPP, EMAIL E SMS
Lo studio puo utilizzare WhatsApp, SMS ed e-mail per: promemoria appuntamenti; variazioni di orario; comunicazioni amministrative; invio di documentazione richiesta dal paziente. L'utilizzo di tali strumenti puo comportare il trattamento del numero telefonico e dell'indirizzo e-mail da parte dei rispettivi fornitori di servizi. Il paziente e consapevole che tali piattaforme operano secondo le proprie informative privacy e condizioni d'uso.

7. TRASFERIMENTO DATI
Qualora vengano utilizzati servizi cloud o strumenti informatici forniti da soggetti terzi, il trattamento avverra nel rispetto delle garanzie previste dal Regolamento UE 2016/679.

8. DIRITTI DELL'INTERESSATO
L'interessato puo esercitare i diritti previsti dagli artt. 15-22 del GDPR: accesso, rettifica, cancellazione, limitazione, opposizione, portabilita dei dati e reclamo all'Autorita Garante.

CONSENSI SPECIFICI
CONSENSO AL TRATTAMENTO OSTEOPATICO: [X] ACCONSENTO      [ ] NON ACCONSENTO
COMUNICAZIONE DEI DATI AL MEDICO CURANTE E/O SPECIALISTI: [X] ACCONSENTO      [ ] NON ACCONSENTO
TRATTAMENTO DEI DATI SANITARI: [X] ACCONSENTO      [ ] NON ACCONSENTO
COMUNICAZIONE DEI DATI AI FAMILIARI: [X] ACCONSENTO      [ ] NON ACCONSENTO
COMUNICAZIONI E PROMEMORIA TRAMITE WHATSAPP: [X] ACCONSENTO      [ ] NON ACCONSENTO
COMUNICAZIONI E PROMEMORIA TRAMITE EMAIL: [X] ACCONSENTO      [ ] NON ACCONSENTO
COMUNICAZIONI E PROMEMORIA TRAMITE SMS: [X] ACCONSENTO      [ ] NON ACCONSENTO

DATA: {{data_consenso}}
FIRMA
TEXT;
    }
}
