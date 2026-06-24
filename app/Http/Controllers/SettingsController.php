<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use App\Support\InvoiceExcelImporter;
use App\Support\InvoiceXmlExporter;
use App\Support\ApplicationBackup;
use App\Support\GoogleCalendarClient;
use App\Support\PrivacyConsentTemplate;
use App\Support\TreatmentSessionDefaults;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        return $this->settingsView($request, 'studio');
    }

    public function patients(Request $request)
    {
        return $this->settingsView($request, 'patients');
    }

    public function users(Request $request)
    {
        return $this->settingsView($request, 'users');
    }

    public function invoices(Request $request)
    {
        return $this->settingsView($request, 'invoices');
    }

    public function sessions(Request $request)
    {
        return $this->settingsView($request, 'sessions');
    }

    public function agenda(Request $request)
    {
        return $this->settingsView($request, 'agenda');
    }

    public function accounting(Request $request)
    {
        return $this->settingsView($request, 'accounting');
    }

    public function privacy(Request $request)
    {
        return $this->settingsView($request, 'privacy');
    }

    public function backup(Request $request)
    {
        return $this->settingsView($request, 'backup');
    }

    public function updateAgenda(Request $request)
    {
        $validated = $request->validate([
            'agenda_start_time' => ['required', 'date_format:H:i'],
            'agenda_end_time' => ['required', 'date_format:H:i', 'after:agenda_start_time'],
            'agenda_slot_minutes' => ['required', 'integer', 'min:15', 'max:120'],
            'agenda_default_duration' => ['required', 'integer', 'min:15', 'max:240'],
            'google_calendar_enabled' => ['nullable', 'boolean'],
            'google_calendar_id' => ['nullable', 'string', 'max:255'],
            'google_calendar_client_id' => ['nullable', 'string', 'max:255'],
            'google_calendar_api_key' => ['nullable', 'string', 'max:255'],
            'google_calendar_sync_mode' => ['nullable', Rule::in(['read', 'write', 'two_way'])],
            'google_calendar_selected_ids' => ['nullable', 'array'],
            'google_calendar_selected_ids.*' => ['string', 'max:255'],
            'categories' => ['nullable', 'array'],
            'categories.*.key' => ['nullable', 'string', 'max:50'],
            'categories.*.label' => ['nullable', 'string', 'max:255'],
            'categories.*.color' => ['nullable', 'string', 'max:20'],
            'categories.*.google_calendar_id' => ['nullable', 'string', 'max:255'],
            'categories.*.sync_patients' => ['nullable', 'boolean'],
        ]);

        foreach ($this->agendaSettingDefinitions() as $key => $definition) {
            $value = $key === 'google_calendar_enabled'
                ? (string) (int) $request->boolean($key)
                : (string) ($validated[$key] ?? $definition['default']);

            Setting::setValue($key, $value, 'agenda');
        }

        $googleCalendarsById = collect(json_decode(Setting::getValue('google_calendar_list', '[]'), true) ?: [])
            ->keyBy('id');

        $categories = collect($validated['categories'] ?? [])
            ->filter(fn (array $category) => filled($category['label'] ?? null))
            ->map(function (array $category, int $index) use ($googleCalendarsById) {
                $googleCalendarId = $category['google_calendar_id'] ?? '';
                $googleCalendarColor = $googleCalendarId
                    ? ($googleCalendarsById->get($googleCalendarId)['backgroundColor'] ?? null)
                    : null;

                return [
                    'key' => $category['key'] ?: 'custom_'.$index,
                    'label' => $category['label'] ?? '',
                    'color' => $googleCalendarColor ?: ($category['color'] ?: '#5f948a'),
                    'google_calendar_id' => $googleCalendarId,
                    'sync_patients' => (bool) ($category['sync_patients'] ?? false),
                ];
            })
            ->values()
            ->all();

        Setting::setValue('agenda_categories', json_encode($categories), 'agenda');
        Setting::setValue('google_calendar_selected_ids', json_encode(array_values($validated['google_calendar_selected_ids'] ?? [])), 'agenda');

        return redirect()
            ->route('settings.agenda')
            ->with('status', 'Impostazioni agenda aggiornate.');
    }

    public function updateSessions(Request $request)
    {
        $validated = $request->validate([
            'rates' => ['nullable', 'array'],
            'rates.*.name' => ['nullable', 'string', 'max:255'],
            'rates.*.amount' => ['nullable', 'numeric', 'min:0'],
            'rates.*.active' => ['nullable', 'boolean'],
            'rates.*.default' => ['nullable', 'boolean'],
        ]);

        $rates = collect($validated['rates'] ?? [])
            ->filter(fn (array $rate) => filled($rate['name'] ?? null) || filled($rate['amount'] ?? null))
            ->map(fn (array $rate) => [
                'name' => $rate['name'] ?? '',
                'amount' => (float) ($rate['amount'] ?? 0),
                'active' => (bool) ($rate['active'] ?? false),
                'default' => (bool) ($rate['default'] ?? false),
            ])
            ->values();

        if ($rates->where('active', true)->isNotEmpty() && $rates->where('active', true)->where('default', true)->isEmpty()) {
            $firstActive = $rates->search(fn (array $rate) => $rate['active']);
            $rates = $rates->map(function (array $rate, int $index) use ($firstActive) {
                $rate['default'] = $index === $firstActive;

                return $rate;
            });
        }

        Setting::setValue('treatment_session_rates', json_encode($rates->all()), 'sessions');

        return redirect()
            ->route('settings.sessions')
            ->with('status', 'Impostazioni sedute aggiornate.');
    }

    public function updateAccounting(Request $request)
    {
        $validated = $request->validate([
            'accounting_tax_regime' => ['required', 'string', 'max:100'],
            'accounting_flat_rate_costs_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'accounting_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'accounting_inps_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'accounting_november_tax_advance_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'accounting_november_inps_advance_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'accounting_november_inps_installments' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        foreach ($this->accountingTaxSettingDefinitions() as $key => $definition) {
            Setting::setValue($key, (string) ($validated[$key] ?? $definition['default']), 'accounting');
        }

        return redirect()
            ->route('settings.accounting')
            ->with('status', 'Impostazioni imposte aggiornate.');
    }

    public function updatePrivacy(Request $request)
    {
        $request->validate([
            'privacy_consent_template' => ['nullable', 'string'],
            'privacy_template_file' => ['nullable', 'file', 'max:10240'],
        ]);

        $template = $request->input('privacy_consent_template', '');

        if ($request->hasFile('privacy_template_file')) {
            $template = $this->privacyTemplateFromUpload($request->file('privacy_template_file'));
        }

        abort_if(blank($template), 422, 'Il documento privacy non puo essere vuoto.');

        Setting::setValue('privacy_consent_template', trim($template), 'privacy');
        Setting::setValue('privacy_consent_template_updated_at', now()->toDateTimeString(), 'privacy');

        return redirect()
            ->route('settings.privacy')
            ->with('status', 'Documento privacy aggiornato.');
    }

    public function updateBackup(Request $request)
    {
        $validated = $request->validate([
            'backup_enabled' => ['nullable', 'boolean'],
            'backup_frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'backup_time' => ['required', 'date_format:H:i'],
            'backup_retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'backup_destination' => ['required', Rule::in(['local', 'external', 'cloud'])],
            'backup_path' => ['nullable', 'string', 'max:500'],
            'backup_database' => ['nullable', 'boolean'],
            'backup_uploaded_files' => ['nullable', 'boolean'],
            'backup_generated_documents' => ['nullable', 'boolean'],
            'backup_logs' => ['nullable', 'boolean'],
            'backup_encrypt' => ['nullable', 'boolean'],
            'backup_notify_email' => ['nullable', 'email', 'max:255'],
            'backup_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach ($this->backupSettingDefinitions() as $key => $definition) {
            $value = in_array($key, [
                'backup_enabled',
                'backup_database',
                'backup_uploaded_files',
                'backup_generated_documents',
                'backup_logs',
                'backup_encrypt',
            ], true)
                ? (string) (int) $request->boolean($key)
                : (string) ($validated[$key] ?? $definition['default']);

            Setting::setValue($key, $value, 'backup');
        }

        return redirect()
            ->route('settings.backup')
            ->with('status', 'Impostazioni backup aggiornate.');
    }

    public function runBackup()
    {
        $result = ApplicationBackup::run();

        $message = 'Backup creato: '.$result['filename'].' - '
            .number_format($result['size'] / 1024 / 1024, 2, ',', '.').' MB';

        if ($result['included'] !== []) {
            $message .= ' - contenuto: '.implode(', ', $result['included']);
        }

        if ($result['warnings'] !== []) {
            $message .= ' - attenzione: '.implode(' ', $result['warnings']);
        }

        return redirect()
            ->route('settings.backup')
            ->with('status', $message);
    }

    public function updateInvoices(Request $request)
    {
        $validated = $request->validate([
            'invoice_transmission_format' => ['nullable', 'string', 'max:20'],
            'invoice_document_type' => ['nullable', 'string', 'max:20'],
            'invoice_currency' => ['nullable', 'string', 'max:10'],
            'invoice_default_recipient_code' => ['nullable', 'string', 'max:20'],
            'invoice_transmitter_country_id' => ['nullable', 'string', 'max:2'],
            'invoice_transmitter_vat_number' => ['nullable', 'string', 'max:50'],
            'invoice_sender_vat_country' => ['nullable', 'string', 'max:2'],
            'invoice_sender_vat_number' => ['nullable', 'string', 'max:50'],
            'invoice_sender_tax_code' => ['nullable', 'string', 'max:50'],
            'invoice_sender_name' => ['nullable', 'string', 'max:255'],
            'invoice_sender_address' => ['nullable', 'string', 'max:255'],
            'invoice_sender_postal_code' => ['nullable', 'string', 'max:20'],
            'invoice_sender_city' => ['nullable', 'string', 'max:100'],
            'invoice_sender_province' => ['nullable', 'string', 'max:10'],
            'invoice_sender_country' => ['nullable', 'string', 'max:2'],
            'invoice_sender_email' => ['nullable', 'email', 'max:255'],
            'invoice_tax_regime' => ['nullable', 'string', 'max:20'],
            'invoice_vat_nature' => ['nullable', 'string', 'max:20'],
            'invoice_vat_reference' => ['nullable', 'string', 'max:255'],
            'invoice_social_security_type' => ['nullable', 'string', 'max:20'],
            'invoice_social_security_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'invoice_payment_method' => ['nullable', 'string', 'max:50'],
            'invoice_payment_terms' => ['nullable', 'string', 'max:50'],
            'invoice_stamp_threshold' => ['nullable', 'numeric', 'min:0'],
            'invoice_stamp_amount' => ['nullable', 'numeric', 'min:0'],
            'invoice_default_causale' => ['nullable', 'string', 'max:1000'],
            'services' => ['nullable', 'array'],
            'services.*.name' => ['nullable', 'string', 'max:255'],
            'services.*.description' => ['nullable', 'string', 'max:255'],
            'services.*.amount' => ['nullable', 'numeric', 'min:0'],
            'services.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'services.*.social_security_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'services.*.vat_nature' => ['nullable', 'string', 'max:20'],
            'services.*.unit_measure' => ['nullable', 'string', 'max:20'],
            'services.*.stamp_duty' => ['nullable', 'boolean'],
        ]);

        foreach ($this->invoiceSettingDefinitions() as $key => $definition) {
            Setting::setValue($key, (string) ($validated[$key] ?? $definition['default']), 'invoice');
        }

        $services = collect($validated['services'] ?? [])
            ->filter(fn (array $service) => filled($service['name'] ?? null) || filled($service['amount'] ?? null))
            ->map(fn (array $service) => [
                'name' => $service['name'] ?? '',
                'description' => $service['description'] ?? '',
                'amount' => (float) ($service['amount'] ?? 0),
                'vat_rate' => (float) ($service['vat_rate'] ?? 0),
                'social_security_rate' => (float) ($service['social_security_rate'] ?? 0),
                'vat_nature' => $service['vat_nature'] ?? '',
                'unit_measure' => $service['unit_measure'] ?? 'PZ',
                'stamp_duty' => (bool) ($service['stamp_duty'] ?? false),
            ])
            ->values()
            ->all();

        Setting::setValue('invoice_services', json_encode($services), 'invoice');

        return redirect()
            ->route('settings.invoices')
            ->with('status', 'Impostazioni fatture aggiornate.');
    }

    public function importInvoices(Request $request)
    {
        $validated = $request->validate([
            'invoices_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $result = InvoiceExcelImporter::import($validated['invoices_file']);

        return redirect()
            ->route('settings.invoices')
            ->with('status', "Importazione fatture completata: {$result['created']} create, {$result['updated']} aggiornate, {$result['skipped']} righe vuote ignorate, {$result['unmatched']} non collegate a un paziente.");
    }

    public function exportInvoicesXml(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;
        $invoices = $this->invoiceExportQuery($from, $to)
            ->with('patient')
            ->orderBy('issued_at')
            ->orderBy('number')
            ->get();

        abort_if($invoices->isEmpty(), 422, 'Nessuna fattura da esportare.');

        $invoices->each->update(['xml_downloaded_at' => now()]);

        $filename = 'export-fatture-xml-'.($from ?: 'inizio').'-'.($to ?: 'fine').'.zip';

        return response(InvoiceXmlExporter::make($invoices), 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private function settingsView(Request $request, string $section)
    {
        $patientExportFrom = $request->date('patient_export_from')?->toDateString();
        $patientExportTo = $request->date('patient_export_to')?->toDateString();
        $invoiceExportFrom = $request->date('invoice_export_from')?->toDateString();
        $invoiceExportTo = $request->date('invoice_export_to')?->toDateString();

        return view('settings.edit', [
            'section' => $section,
            'settings' => $this->settings(),
            'values' => $this->values(),
            'users' => User::orderBy('name')->get(),
            'invoiceSettings' => $this->invoiceValues(),
            'invoiceServices' => $this->invoiceServices(),
            'sessionRates' => TreatmentSessionDefaults::rates(),
            'agendaSettings' => $this->agendaValues(),
            'agendaCategories' => $this->agendaCategories(),
            'googleCalendarStatus' => GoogleCalendarClient::status(),
            'googleCalendars' => GoogleCalendarClient::storedCalendarList(),
            'selectedGoogleCalendarIds' => GoogleCalendarClient::selectedCalendarIds(),
            'accountingTaxSettings' => $this->accountingTaxValues(),
            'privacyTemplate' => PrivacyConsentTemplate::text(),
            'privacyTemplateUpdatedAt' => Setting::getValue('privacy_consent_template_updated_at'),
            'backupSettings' => $this->backupValues(),
            'patientExportFrom' => $patientExportFrom,
            'patientExportTo' => $patientExportTo,
            'patientExportCount' => $this->patientExportQuery($patientExportFrom, $patientExportTo)->count(),
            'invoiceExportFrom' => $invoiceExportFrom,
            'invoiceExportTo' => $invoiceExportTo,
            'invoiceExportCount' => $this->invoiceExportQuery($invoiceExportFrom, $invoiceExportTo)->count(),
            'patientExportQuickLinks' => [
                'week' => [
                    'label' => 'Ultima settimana',
                    'url' => route('settings.patients', [
                        'patient_export_from' => now()->subWeek()->toDateString(),
                        'patient_export_to' => now()->toDateString(),
                    ]),
                ],
                'month' => [
                    'label' => 'Ultimo mese',
                    'url' => route('settings.patients', [
                        'patient_export_from' => now()->subMonth()->toDateString(),
                        'patient_export_to' => now()->toDateString(),
                    ]),
                ],
                'all' => [
                    'label' => 'Tutti',
                    'url' => route('settings.patients'),
                ],
            ],
            'invoiceExportQuickLinks' => [
                'week' => [
                    'label' => 'Ultima settimana',
                    'url' => route('settings.invoices', [
                        'invoice_export_from' => now()->subWeek()->toDateString(),
                        'invoice_export_to' => now()->toDateString(),
                    ]),
                ],
                'month' => [
                    'label' => 'Ultimo mese',
                    'url' => route('settings.invoices', [
                        'invoice_export_from' => now()->subMonth()->toDateString(),
                        'invoice_export_to' => now()->toDateString(),
                    ]),
                ],
                'all' => [
                    'label' => 'Tutte',
                    'url' => route('settings.invoices'),
                ],
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate($this->rules());

        foreach ($this->settings() as $key => $definition) {
            Setting::setValue($key, $validated[$key] ?? null, $definition['group']);
        }

        return back()->with('status', 'Impostazioni aggiornate.');
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create($validated);

        return back()->with('status', 'Utente creato correttamente.');
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $user->update($validated);

        return back()->with('status', 'Utente aggiornato correttamente.');
    }

    public function destroyUser(User $user)
    {
        abort_if(User::count() <= 1, 422, 'Non puoi eliminare l\'ultimo utente.');

        $user->delete();

        return back()->with('status', 'Utente eliminato.');
    }

    private function values(): array
    {
        return collect($this->settings())
            ->mapWithKeys(fn (array $definition, string $key) => [
                $key => Setting::getValue($key, $definition['default'] ?? null),
            ])
            ->all();
    }

    private function rules(): array
    {
        return collect($this->settings())
            ->mapWithKeys(fn (array $definition, string $key) => [$key => $definition['rules']])
            ->all();
    }

    private function privacyTemplateFromUpload($file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'txt') {
            return trim((string) file_get_contents($file->getRealPath()));
        }

        if ($extension === 'docx') {
            return $this->textFromDocx($file->getRealPath());
        }

        abort(422, 'Carica un file .txt oppure .docx.');
    }

    private function textFromDocx(string $path): string
    {
        abort_unless(class_exists(\ZipArchive::class), 422, 'La lettura dei file Word non e disponibile su questo PC.');

        $zip = new \ZipArchive();
        abort_if($zip->open($path) !== true, 422, 'Non riesco ad aprire il file Word caricato.');

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        abort_if($xml === false, 422, 'Il file Word non contiene un documento leggibile.');

        $xml = preg_replace('/<\/w:p>/', "\n\n", $xml);
        $xml = preg_replace('/<w:tab\/>/', "\t", $xml);
        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    private function settings(): array
    {
        return [
            'practice_name' => [
                'label' => 'Nome studio',
                'group' => 'studio',
                'type' => 'text',
                'rules' => ['required', 'string', 'max:255'],
                'default' => config('app.name', 'Studio Osteopatico'),
            ],
            'practice_owner' => [
                'label' => 'Titolare / professionista',
                'group' => 'studio',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
                'default' => null,
            ],
            'practice_email' => [
                'label' => 'Email studio',
                'group' => 'studio',
                'type' => 'email',
                'rules' => ['nullable', 'email', 'max:255'],
                'default' => null,
            ],
            'practice_phone' => [
                'label' => 'Telefono studio',
                'group' => 'studio',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
                'default' => null,
            ],
            'practice_address' => [
                'label' => 'Indirizzo studio',
                'group' => 'studio',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
                'default' => null,
            ],
            'vat_number' => [
                'label' => 'Partita IVA',
                'group' => 'billing',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:50'],
                'default' => null,
            ],
            'tax_code' => [
                'label' => 'Codice fiscale studio',
                'group' => 'billing',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:50'],
                'default' => null,
            ],
            'invoice_prefix' => [
                'label' => 'Prefisso fatture',
                'group' => 'billing',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:20'],
                'default' => 'F',
            ],
            'default_session_fee' => [
                'label' => 'Importo seduta predefinito',
                'group' => 'operations',
                'type' => 'number',
                'rules' => ['nullable', 'numeric', 'min:0'],
                'default' => null,
            ],
            'appointment_duration' => [
                'label' => 'Durata seduta predefinita (minuti)',
                'group' => 'operations',
                'type' => 'number',
                'rules' => ['nullable', 'integer', 'min:1'],
                'default' => '60',
            ],
        ];
    }

    private function invoiceSettingDefinitions(): array
    {
        return [
            'invoice_transmission_format' => ['default' => 'FPR12'],
            'invoice_document_type' => ['default' => 'TD01'],
            'invoice_currency' => ['default' => 'EUR'],
            'invoice_default_recipient_code' => ['default' => '0000000'],
            'invoice_transmitter_country_id' => ['default' => 'IT'],
            'invoice_transmitter_vat_number' => ['default' => '01879020517'],
            'invoice_sender_vat_country' => ['default' => 'IT'],
            'invoice_sender_vat_number' => ['default' => '02429900414'],
            'invoice_sender_tax_code' => ['default' => 'FLPDNL85R01D488C'],
            'invoice_sender_name' => ['default' => 'Filipponi Danilo'],
            'invoice_sender_address' => ['default' => 'via Madonna Ponte 33'],
            'invoice_sender_postal_code' => ['default' => '61032'],
            'invoice_sender_city' => ['default' => 'Fano'],
            'invoice_sender_province' => ['default' => 'PU'],
            'invoice_sender_country' => ['default' => 'IT'],
            'invoice_sender_email' => ['default' => 'danilo.filipponi@gmail.com'],
            'invoice_tax_regime' => ['default' => 'RF19'],
            'invoice_vat_nature' => ['default' => 'N2.2'],
            'invoice_vat_reference' => ['default' => 'Non soggette - altri casi'],
            'invoice_social_security_type' => ['default' => 'TC22'],
            'invoice_social_security_rate' => ['default' => '4.00'],
            'invoice_payment_method' => ['default' => 'MP08'],
            'invoice_payment_terms' => ['default' => 'TP02'],
            'invoice_stamp_threshold' => ['default' => '77.47'],
            'invoice_stamp_amount' => ['default' => '2.00'],
            'invoice_default_causale' => ['default' => 'Operazione non soggetta a ritenuta alla fonte a titolo di acconto ai sensi dell\'articolo 1, comma 67, l. n. 190 del 2014 e successive modificazioni'],
        ];
    }

    private function invoiceValues(): array
    {
        return collect($this->invoiceSettingDefinitions())
            ->mapWithKeys(fn (array $definition, string $key) => [
                $key => Setting::getValue($key, $definition['default']),
            ])
            ->all();
    }

    private function accountingTaxSettingDefinitions(): array
    {
        return [
            'accounting_tax_regime' => ['default' => 'Regime forfettario'],
            'accounting_flat_rate_costs_rate' => ['default' => '22'],
            'accounting_tax_rate' => ['default' => '15'],
            'accounting_inps_rate' => ['default' => '25.98'],
            'accounting_november_tax_advance_rate' => ['default' => '60'],
            'accounting_november_inps_advance_rate' => ['default' => '80'],
            'accounting_november_inps_installments' => ['default' => '2'],
        ];
    }

    private function accountingTaxValues(): array
    {
        return collect($this->accountingTaxSettingDefinitions())
            ->mapWithKeys(fn (array $definition, string $key) => [
                $key => Setting::getValue($key, $definition['default']),
            ])
            ->all();
    }

    private function backupSettingDefinitions(): array
    {
        return [
            'backup_enabled' => ['default' => '1'],
            'backup_frequency' => ['default' => 'daily'],
            'backup_time' => ['default' => '22:00'],
            'backup_retention_days' => ['default' => '30'],
            'backup_destination' => ['default' => 'local'],
            'backup_path' => ['default' => 'storage/app/backups'],
            'backup_database' => ['default' => '1'],
            'backup_uploaded_files' => ['default' => '1'],
            'backup_generated_documents' => ['default' => '1'],
            'backup_logs' => ['default' => '0'],
            'backup_encrypt' => ['default' => '0'],
            'backup_notify_email' => ['default' => ''],
            'backup_notes' => ['default' => ''],
        ];
    }

    private function backupValues(): array
    {
        return collect($this->backupSettingDefinitions())
            ->mapWithKeys(fn (array $definition, string $key) => [
                $key => Setting::getValue($key, $definition['default']),
            ])
            ->all();
    }

    private function invoiceServices(): array
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
            [
                'name' => 'Prima visita osteopatica',
                'description' => 'Valutazione iniziale e trattamento',
                'amount' => 90,
                'vat_rate' => 0,
                'social_security_rate' => 4,
                'vat_nature' => 'N2.2',
                'unit_measure' => 'PZ',
                'stamp_duty' => true,
            ],
        ];
    }

    private function agendaSettingDefinitions(): array
    {
        return [
            'agenda_start_time' => ['default' => '08:00'],
            'agenda_end_time' => ['default' => '20:00'],
            'agenda_slot_minutes' => ['default' => '30'],
            'agenda_default_duration' => ['default' => '60'],
            'google_calendar_enabled' => ['default' => '0'],
            'google_calendar_id' => ['default' => config('services.google_calendar.calendar_id', 'primary')],
            'google_calendar_client_id' => ['default' => config('services.google_calendar.client_id', '')],
            'google_calendar_api_key' => ['default' => ''],
            'google_calendar_sync_mode' => ['default' => 'read'],
        ];
    }

    private function agendaValues(): array
    {
        return collect($this->agendaSettingDefinitions())
            ->mapWithKeys(fn (array $definition, string $key) => [
                $key => Setting::getValue($key, $definition['default']),
            ])
            ->all();
    }

    private function agendaCategories(): array
    {
        $categories = json_decode(Setting::getValue('agenda_categories', '[]'), true) ?: [];

        if ($categories !== []) {
            return $categories;
        }

        return [
            ['key' => 'visit', 'label' => 'Visita osteopatica', 'color' => '#5f948a', 'google_calendar_id' => '', 'sync_patients' => true],
            ['key' => 'personal', 'label' => 'Impegno personale', 'color' => '#64748b', 'google_calendar_id' => '', 'sync_patients' => false],
            ['key' => 'holiday', 'label' => 'Ferie', 'color' => '#d97706', 'google_calendar_id' => '', 'sync_patients' => false],
            ['key' => 'absence', 'label' => 'Assenza', 'color' => '#dc2626', 'google_calendar_id' => '', 'sync_patients' => false],
        ];
    }

    private function patientExportQuery(?string $from, ?string $to)
    {
        return Patient::where('user_id', Auth::id())
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to));
    }

    private function invoiceExportQuery(?string $from, ?string $to)
    {
        return Invoice::whereHas('patient', fn ($query) => $query->where('user_id', Auth::id()))
            ->when($from, fn ($query) => $query->whereDate('issued_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('issued_at', '<=', $to));
    }
}
