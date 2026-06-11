<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use App\Support\InvoiceExcelImporter;
use App\Support\InvoiceXmlExporter;
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
            'invoices_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
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
