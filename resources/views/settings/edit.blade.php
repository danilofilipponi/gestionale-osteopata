<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Impostazioni</h2>
                <p class="mt-1 text-sm text-gray-500">Configurazione di base dello studio e area amministrativa.</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist">Profilo account</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="app-section space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
                <div class="space-y-6">
                    @if ($section === 'patients')
                    <section class="app-card p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-gray-900">Impostazioni pazienti</h3>
                                <p class="mt-1 text-sm text-gray-500">Esportazione anagrafiche pazienti in formato Excel.</p>
                            </div>
                            <span class="rounded-full bg-mist px-3 py-1 text-xs font-bold uppercase text-sage">Export</span>
                        </div>

                        <form method="GET" action="{{ route('settings.patients') }}" class="mt-5 grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
                            <div>
                                <x-input-label for="patient_export_from" value="Data inizio" />
                                <x-text-input id="patient_export_from" name="patient_export_from" type="date" class="mt-1 block w-full" :value="$patientExportFrom" />
                            </div>
                            <div>
                                <x-input-label for="patient_export_to" value="Data fine" />
                                <x-text-input id="patient_export_to" name="patient_export_to" type="date" class="mt-1 block w-full" :value="$patientExportTo" />
                            </div>
                            <button class="rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist">Applica filtro</button>
                        </form>

                        <div class="mt-4 flex flex-wrap gap-3">
                            @foreach ($patientExportQuickLinks as $quickLink)
                                <a href="{{ $quickLink['url'] }}" class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-muted hover:bg-mist hover:text-ink">{{ $quickLink['label'] }}</a>
                            @endforeach
                        </div>

                        <div class="mt-5 rounded-2xl border border-line bg-mist p-5">
                            <p class="text-sm font-bold text-muted">Pazienti che saranno esportati</p>
                            <p class="mt-1 text-3xl font-bold text-ink">{{ $patientExportCount }}</p>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <a href="{{ route('patients.export', ['from' => $patientExportFrom, 'to' => $patientExportTo]) }}" class="inline-flex items-center rounded-xl bg-sage px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#4f7f75]">
                                Esporta Excel pazienti
                            </a>
                        </div>
                    </section>

                    <section class="app-card p-6">
                        <div>
                            <h3 class="font-semibold text-gray-900">Importazione Excel pazienti</h3>
                            <p class="mt-1 text-sm text-gray-500">Carica un file Excel creato con l'esportazione pazienti per creare o aggiornare le anagrafiche.</p>
                        </div>

                        <form method="POST" action="{{ route('patients.import') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                            @csrf
                            <div>
                                <x-input-label for="patients_file" value="File Excel pazienti" />
                                <input id="patients_file" name="patients_file" type="file" accept=".xlsx" class="app-field mt-1 block w-full" required>
                                <x-input-error :messages="$errors->get('patients_file')" class="mt-2" />
                                <p class="mt-2 text-xs text-muted">Formato richiesto: foglio ImportAnagrafiche generato dall'export pazienti.</p>
                            </div>
                            <x-primary-button>Importa Excel pazienti</x-primary-button>
                        </form>
                    </section>
                    @endif

                    @if ($section === 'studio')
                    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        @foreach ([
                            'studio' => 'Dati studio',
                        ] as $group => $title)
                            <section class="app-card p-6">
                                <h3 class="font-semibold text-gray-900">{{ $title }}</h3>
                                <div class="mt-4 grid gap-4 md:grid-cols-2">
                                    @foreach ($settings as $key => $setting)
                                        @continue($setting['group'] !== $group)
                                        <div class="{{ $key === 'practice_address' ? 'md:col-span-2' : '' }}">
                                            <x-input-label :for="$key" :value="$setting['label']" />
                                            <x-text-input
                                                :id="$key"
                                                :name="$key"
                                                :type="$setting['type']"
                                                class="mt-1 block w-full"
                                                :value="old($key, $values[$key])"
                                                :step="$setting['type'] === 'number' ? '0.01' : null"
                                            />
                                            <x-input-error :messages="$errors->get($key)" class="mt-2" />
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach

                        <div class="flex justify-end">
                            <x-primary-button>Salva impostazioni</x-primary-button>
                        </div>
                    </form>
                    @endif

                    @if ($section === 'invoices')
                    <form method="POST" action="{{ route('settings.invoices.update') }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <section class="app-card p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-semibold text-gray-900">Impostazioni default fatture</h3>
                                    <p class="mt-1 text-sm text-gray-500">Parametri base predisposti per il modello Aruba fatture elettroniche.</p>
                                </div>
                                <span class="rounded-full bg-mist px-3 py-1 text-xs font-bold uppercase text-sage">Aruba</span>
                            </div>

                            <div class="mt-5 space-y-6">
                                <div>
                                    <h4 class="text-sm font-bold uppercase text-muted">Tracciato e documento</h4>
                                    <div class="mt-3 grid gap-4 md:grid-cols-4">
                                        <div>
                                            <x-input-label for="invoice_transmission_format" value="Formato trasmissione" />
                                            <x-text-input id="invoice_transmission_format" name="invoice_transmission_format" class="mt-1 block w-full" :value="old('invoice_transmission_format', $invoiceSettings['invoice_transmission_format'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_document_type" value="Tipo documento" />
                                            <x-text-input id="invoice_document_type" name="invoice_document_type" class="mt-1 block w-full" :value="old('invoice_document_type', $invoiceSettings['invoice_document_type'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_currency" value="Divisa" />
                                            <x-text-input id="invoice_currency" name="invoice_currency" class="mt-1 block w-full" :value="old('invoice_currency', $invoiceSettings['invoice_currency'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_default_recipient_code" value="Codice destinatario default" />
                                            <x-text-input id="invoice_default_recipient_code" name="invoice_default_recipient_code" class="mt-1 block w-full" :value="old('invoice_default_recipient_code', $invoiceSettings['invoice_default_recipient_code'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_transmitter_country_id" value="Id paese trasmittente" />
                                            <x-text-input id="invoice_transmitter_country_id" name="invoice_transmitter_country_id" class="mt-1 block w-full" :value="old('invoice_transmitter_country_id', $invoiceSettings['invoice_transmitter_country_id'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_transmitter_vat_number" value="Id codice trasmittente" />
                                            <x-text-input id="invoice_transmitter_vat_number" name="invoice_transmitter_vat_number" class="mt-1 block w-full" :value="old('invoice_transmitter_vat_number', $invoiceSettings['invoice_transmitter_vat_number'])" />
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-sm font-bold uppercase text-muted">Cedente / prestatore</h4>
                                    <div class="mt-3 grid gap-4 md:grid-cols-3">
                                        <div>
                                            <x-input-label for="invoice_sender_name" value="Denominazione" />
                                            <x-text-input id="invoice_sender_name" name="invoice_sender_name" class="mt-1 block w-full" :value="old('invoice_sender_name', $invoiceSettings['invoice_sender_name'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_sender_vat_number" value="Partita IVA" />
                                            <x-text-input id="invoice_sender_vat_number" name="invoice_sender_vat_number" class="mt-1 block w-full" :value="old('invoice_sender_vat_number', $invoiceSettings['invoice_sender_vat_number'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_sender_tax_code" value="Codice fiscale" />
                                            <x-text-input id="invoice_sender_tax_code" name="invoice_sender_tax_code" class="mt-1 block w-full" :value="old('invoice_sender_tax_code', $invoiceSettings['invoice_sender_tax_code'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_sender_vat_country" value="Id paese IVA" />
                                            <x-text-input id="invoice_sender_vat_country" name="invoice_sender_vat_country" class="mt-1 block w-full" :value="old('invoice_sender_vat_country', $invoiceSettings['invoice_sender_vat_country'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_tax_regime" value="Regime fiscale" />
                                            <x-text-input id="invoice_tax_regime" name="invoice_tax_regime" class="mt-1 block w-full" :value="old('invoice_tax_regime', $invoiceSettings['invoice_tax_regime'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_sender_email" value="Email" />
                                            <x-text-input id="invoice_sender_email" name="invoice_sender_email" type="email" class="mt-1 block w-full" :value="old('invoice_sender_email', $invoiceSettings['invoice_sender_email'])" />
                                        </div>
                                        <div class="md:col-span-2">
                                            <x-input-label for="invoice_sender_address" value="Indirizzo" />
                                            <x-text-input id="invoice_sender_address" name="invoice_sender_address" class="mt-1 block w-full" :value="old('invoice_sender_address', $invoiceSettings['invoice_sender_address'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_sender_postal_code" value="CAP" />
                                            <x-text-input id="invoice_sender_postal_code" name="invoice_sender_postal_code" class="mt-1 block w-full" :value="old('invoice_sender_postal_code', $invoiceSettings['invoice_sender_postal_code'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_sender_city" value="Comune" />
                                            <x-text-input id="invoice_sender_city" name="invoice_sender_city" class="mt-1 block w-full" :value="old('invoice_sender_city', $invoiceSettings['invoice_sender_city'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_sender_province" value="Provincia" />
                                            <x-text-input id="invoice_sender_province" name="invoice_sender_province" class="mt-1 block w-full" :value="old('invoice_sender_province', $invoiceSettings['invoice_sender_province'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_sender_country" value="Nazione" />
                                            <x-text-input id="invoice_sender_country" name="invoice_sender_country" class="mt-1 block w-full" :value="old('invoice_sender_country', $invoiceSettings['invoice_sender_country'])" />
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-sm font-bold uppercase text-muted">IVA, cassa e pagamento</h4>
                                    <div class="mt-3 grid gap-4 md:grid-cols-3">
                                        <div>
                                            <x-input-label for="invoice_vat_nature" value="Natura IVA" />
                                            <x-text-input id="invoice_vat_nature" name="invoice_vat_nature" class="mt-1 block w-full" :value="old('invoice_vat_nature', $invoiceSettings['invoice_vat_nature'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_vat_reference" value="Riferimento normativo IVA" />
                                            <x-text-input id="invoice_vat_reference" name="invoice_vat_reference" class="mt-1 block w-full" :value="old('invoice_vat_reference', $invoiceSettings['invoice_vat_reference'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_social_security_type" value="Tipo cassa" />
                                            <x-text-input id="invoice_social_security_type" name="invoice_social_security_type" class="mt-1 block w-full" :value="old('invoice_social_security_type', $invoiceSettings['invoice_social_security_type'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_social_security_rate" value="Aliquota cassa" />
                                            <x-text-input id="invoice_social_security_rate" name="invoice_social_security_rate" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('invoice_social_security_rate', $invoiceSettings['invoice_social_security_rate'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_payment_method" value="Metodo pagamento" />
                                            <x-text-input id="invoice_payment_method" name="invoice_payment_method" class="mt-1 block w-full" :value="old('invoice_payment_method', $invoiceSettings['invoice_payment_method'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_payment_terms" value="Condizioni pagamento" />
                                            <x-text-input id="invoice_payment_terms" name="invoice_payment_terms" class="mt-1 block w-full" :value="old('invoice_payment_terms', $invoiceSettings['invoice_payment_terms'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_stamp_threshold" value="Soglia bollo" />
                                            <x-text-input id="invoice_stamp_threshold" name="invoice_stamp_threshold" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('invoice_stamp_threshold', $invoiceSettings['invoice_stamp_threshold'])" />
                                        </div>
                                        <div>
                                            <x-input-label for="invoice_stamp_amount" value="Importo bollo" />
                                            <x-text-input id="invoice_stamp_amount" name="invoice_stamp_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('invoice_stamp_amount', $invoiceSettings['invoice_stamp_amount'])" />
                                        </div>
                                        <div class="md:col-span-3">
                                            <x-input-label for="invoice_default_causale" value="Causale default" />
                                            <textarea id="invoice_default_causale" name="invoice_default_causale" rows="3" class="app-field mt-1 block w-full">{{ old('invoice_default_causale', $invoiceSettings['invoice_default_causale']) }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8">
                                <h4 class="text-sm font-bold uppercase text-muted">Servizi selezionabili in fattura</h4>
                                <div class="mt-4 overflow-x-auto">
                                    <table class="w-full min-w-[980px] text-left text-sm">
                                        <thead>
                                            <tr class="border-b border-line text-xs uppercase text-muted">
                                                <th class="pb-3">Servizio</th>
                                                <th class="pb-3">Descrizione</th>
                                                <th class="pb-3">Costo</th>
                                                <th class="pb-3">Aliquota</th>
                                                <th class="pb-3">Cassa</th>
                                                <th class="pb-3">Natura</th>
                                                <th class="pb-3">IVA</th>
                                                <th class="pb-3">Bollo</th>
                                                <th class="pb-3">Totale</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-line">
                                            @for ($index = 0; $index < 5; $index++)
                                                @php
                                                    $service = old("services.$index", $invoiceServices[$index] ?? []);
                                                    $amount = (float) ($service['amount'] ?? 0);
                                                    $vatRate = (float) ($service['vat_rate'] ?? 0);
                                                    $socialSecurityRate = (float) ($service['social_security_rate'] ?? $invoiceSettings['invoice_social_security_rate']);
                                                    $stampDuty = (bool) ($service['stamp_duty'] ?? false);
                                                    $socialSecurityAmount = $amount * $socialSecurityRate / 100;
                                                    $taxable = $amount + $socialSecurityAmount;
                                                    $vat = $taxable * $vatRate / 100;
                                                    $stampAmount = $stampDuty && $taxable > (float) $invoiceSettings['invoice_stamp_threshold'] ? (float) $invoiceSettings['invoice_stamp_amount'] : 0;
                                                @endphp
                                                <tr>
                                                    <td class="py-3 pr-3"><input name="services[{{ $index }}][name]" class="app-field w-44" value="{{ $service['name'] ?? '' }}"></td>
                                                    <td class="py-3 pr-3"><input name="services[{{ $index }}][description]" class="app-field w-56" value="{{ $service['description'] ?? '' }}"></td>
                                                    <td class="py-3 pr-3"><input name="services[{{ $index }}][amount]" type="number" step="0.01" min="0" class="app-field w-28" value="{{ $service['amount'] ?? '' }}"></td>
                                                    <td class="py-3 pr-3"><input name="services[{{ $index }}][vat_rate]" type="number" step="0.01" min="0" max="100" class="app-field w-24" value="{{ $service['vat_rate'] ?? '0' }}"></td>
                                                    <td class="py-3 pr-3"><input name="services[{{ $index }}][social_security_rate]" type="number" step="0.01" min="0" max="100" class="app-field w-24" value="{{ $service['social_security_rate'] ?? $invoiceSettings['invoice_social_security_rate'] }}"></td>
                                                    <td class="py-3 pr-3"><input name="services[{{ $index }}][vat_nature]" class="app-field w-24" value="{{ $service['vat_nature'] ?? $invoiceSettings['invoice_vat_nature'] }}"></td>
                                                    <input type="hidden" name="services[{{ $index }}][unit_measure]" value="{{ $service['unit_measure'] ?? 'PZ' }}">
                                                    <td class="py-3 pr-3 font-medium text-ink">EUR {{ number_format($vat, 2, ',', '.') }}</td>
                                                    <td class="py-3 pr-3">
                                                        <label class="flex items-center gap-2 text-sm text-muted">
                                                            <input type="checkbox" name="services[{{ $index }}][stamp_duty]" value="1" @checked($stampDuty) class="rounded border-gray-300 text-sage focus:ring-sage">
                                                            EUR {{ number_format($stampAmount, 2, ',', '.') }}
                                                        </label>
                                                    </td>
                                                    <td class="py-3 pr-3 font-bold text-ink">EUR {{ number_format($taxable + $vat + $stampAmount, 2, ',', '.') }}</td>
                                                </tr>
                                            @endfor
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="mt-5 flex justify-end">
                                <x-primary-button>Salva impostazioni fatture</x-primary-button>
                            </div>
                        </section>
                    </form>

                    <section class="app-card p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-gray-900">Esportazione XML fatture</h3>
                                <p class="mt-1 text-sm text-gray-500">Genera uno ZIP con XML fattura elettronica sulla base del modello Aruba configurato.</p>
                            </div>
                            <span class="rounded-full bg-mist px-3 py-1 text-xs font-bold uppercase text-sage">XML</span>
                        </div>

                        <form method="GET" action="{{ route('settings.invoices') }}" class="mt-5 grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
                            <div>
                                <x-input-label for="invoice_export_from" value="Data inizio" />
                                <x-text-input id="invoice_export_from" name="invoice_export_from" type="date" class="mt-1 block w-full" :value="$invoiceExportFrom" />
                            </div>
                            <div>
                                <x-input-label for="invoice_export_to" value="Data fine" />
                                <x-text-input id="invoice_export_to" name="invoice_export_to" type="date" class="mt-1 block w-full" :value="$invoiceExportTo" />
                            </div>
                            <button class="rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist">Applica filtro</button>
                        </form>

                        <div class="mt-4 flex flex-wrap gap-3">
                            @foreach ($invoiceExportQuickLinks as $quickLink)
                                <a href="{{ $quickLink['url'] }}" class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-muted hover:bg-mist hover:text-ink">{{ $quickLink['label'] }}</a>
                            @endforeach
                        </div>

                        <div class="mt-5 rounded-2xl border border-line bg-mist p-5">
                            <p class="text-sm font-bold text-muted">Fatture che saranno esportate</p>
                            <p class="mt-1 text-3xl font-bold text-ink">{{ $invoiceExportCount }}</p>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <a href="{{ route('settings.invoices.export-xml', ['from' => $invoiceExportFrom, 'to' => $invoiceExportTo]) }}" class="inline-flex items-center rounded-xl bg-sage px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#4f7f75]">
                                Esporta fatture XML
                            </a>
                        </div>
                    </section>

                    <section class="app-card p-6">
                        <h3 class="font-semibold text-gray-900">Importazione Excel fatture</h3>
                        <p class="mt-1 text-sm text-gray-500">Importa lo storico fatture collegandolo al paziente tramite la colonna Idpaziente.</p>

                        <form method="POST" action="{{ route('settings.invoices.import') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                            @csrf
                            <div>
                                <x-input-label for="invoices_file" value="File Excel fatture" />
                                <input id="invoices_file" name="invoices_file" type="file" accept=".xlsx" class="app-field mt-1 block w-full" required>
                                <x-input-error :messages="$errors->get('invoices_file')" class="mt-2" />
                                <p class="mt-2 text-xs text-muted">Colonne lette: IDFattura, N Fattura, Data di emissione, Idpaziente, Descrizione, Importo, Inps, Bollo, Totale.</p>
                            </div>
                            <x-primary-button>Importa Excel fatture</x-primary-button>
                        </form>

                        <div class="mt-5 rounded-2xl border border-line bg-mist p-5 text-sm text-muted">
                            Se una fattura con lo stesso numero esiste gia nella scheda del paziente, viene aggiornata. Le righe con Idpaziente non trovato vengono saltate e conteggiate nel report finale.
                        </div>
                    </section>
                    @endif

                    @if ($section === 'users')
                    <section class="app-card p-6">
                        <h3 class="font-semibold text-gray-900">Nuovo utente</h3>
                        <form method="POST" action="{{ route('settings.users.store') }}" class="mt-4 grid gap-4 md:grid-cols-2">
                            @csrf
                            <div>
                                <x-input-label for="new_user_name" value="Nome" />
                                <x-text-input id="new_user_name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="new_user_email" value="Email" />
                                <x-text-input id="new_user_email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="new_user_password" value="Password" />
                                <x-text-input id="new_user_password" name="password" type="password" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="new_user_password_confirmation" value="Conferma password" />
                                <x-text-input id="new_user_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                            </div>
                            <div class="md:col-span-2">
                                <x-primary-button>Crea utente</x-primary-button>
                            </div>
                        </form>
                    </section>

                    <section class="app-card p-6">
                        <h3 class="font-semibold text-gray-900">Utenti esistenti</h3>
                        <div class="mt-4 divide-y divide-gray-100">
                            @foreach ($users as $user)
                                <div class="py-5">
                                    <form method="POST" action="{{ route('settings.users.update', $user) }}" class="grid gap-4 md:grid-cols-2">
                                        @csrf
                                        @method('PATCH')
                                        <div>
                                            <x-input-label :for="'user_name_'.$user->id" value="Nome" />
                                            <x-text-input :id="'user_name_'.$user->id" name="name" class="mt-1 block w-full" :value="$user->name" required />
                                        </div>
                                        <div>
                                            <x-input-label :for="'user_email_'.$user->id" value="Email" />
                                            <x-text-input :id="'user_email_'.$user->id" name="email" type="email" class="mt-1 block w-full" :value="$user->email" required />
                                        </div>
                                        <div>
                                            <x-input-label :for="'user_password_'.$user->id" value="Nuova password" />
                                            <x-text-input :id="'user_password_'.$user->id" name="password" type="password" class="mt-1 block w-full" />
                                        </div>
                                        <div>
                                            <x-input-label :for="'user_password_confirmation_'.$user->id" value="Conferma nuova password" />
                                            <x-text-input :id="'user_password_confirmation_'.$user->id" name="password_confirmation" type="password" class="mt-1 block w-full" />
                                        </div>
                                        <div class="flex flex-wrap items-center justify-between gap-3 md:col-span-2">
                                            <span class="text-sm text-gray-500">Aggiornato {{ $user->updated_at->format('d/m/Y H:i') }}</span>
                                            <div class="flex gap-3">
                                                @if ($users->count() > 1)
                                                    <button form="delete-user-{{ $user->id }}" class="rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50" onclick="return confirm('Eliminare questo utente?')">Elimina</button>
                                                @endif
                                                <x-primary-button>Salva utente</x-primary-button>
                                            </div>
                                        </div>
                                    </form>
                                    <form id="delete-user-{{ $user->id }}" method="POST" action="{{ route('settings.users.destroy', $user) }}" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </section>
                    @endif

                    @if ($section === 'agenda')
                    <form method="POST" action="{{ route('settings.agenda.update') }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <section class="app-card p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-semibold text-gray-900">Impostazioni agenda</h3>
                                    <p class="mt-1 text-sm text-gray-500">Orari, durata appuntamenti, categorie e predisposizione Google Calendar.</p>
                                </div>
                                <span class="rounded-full bg-mist px-3 py-1 text-xs font-bold uppercase text-sage">Calendario</span>
                            </div>

                            <div class="mt-5 grid gap-4 md:grid-cols-4">
                                <div>
                                    <x-input-label for="agenda_start_time" value="Ora inizio giornata" />
                                    <x-text-input id="agenda_start_time" name="agenda_start_time" type="time" class="mt-1 block w-full" :value="old('agenda_start_time', $agendaSettings['agenda_start_time'])" required />
                                    <x-input-error :messages="$errors->get('agenda_start_time')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="agenda_end_time" value="Ora fine giornata" />
                                    <x-text-input id="agenda_end_time" name="agenda_end_time" type="time" class="mt-1 block w-full" :value="old('agenda_end_time', $agendaSettings['agenda_end_time'])" required />
                                    <x-input-error :messages="$errors->get('agenda_end_time')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="agenda_slot_minutes" value="Intervallo griglia" />
                                    <select id="agenda_slot_minutes" name="agenda_slot_minutes" class="app-field mt-1 block w-full">
                                        @foreach ([15, 30, 45, 60] as $minutes)
                                            <option value="{{ $minutes }}" @selected((int) old('agenda_slot_minutes', $agendaSettings['agenda_slot_minutes']) === $minutes)>{{ $minutes }} minuti</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="agenda_default_duration" value="Durata appuntamento" />
                                    <select id="agenda_default_duration" name="agenda_default_duration" class="app-field mt-1 block w-full">
                                        @foreach ([30, 45, 60, 75, 90, 120] as $minutes)
                                            <option value="{{ $minutes }}" @selected((int) old('agenda_default_duration', $agendaSettings['agenda_default_duration']) === $minutes)>{{ $minutes }} minuti</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </section>

                        <section class="app-card p-6">
                            <h3 class="font-semibold text-gray-900">Categorie appuntamento</h3>
                            <p class="mt-1 text-sm text-gray-500">Le categorie attive compaiono nella tendina della pagina agenda e ne determinano il colore.</p>

                            <div class="mt-5 overflow-x-auto">
                                <table class="w-full min-w-[760px] text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-line text-xs uppercase text-muted">
                                            <th class="pb-3">Categoria</th>
                                            <th class="pb-3">Colore</th>
                                            <th class="pb-3">Calendario Google</th>
                                            <th class="pb-3">Sincronizza pazienti</th>
                                            <th class="pb-3">Anteprima</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-line">
                                        @for ($index = 0; $index < 8; $index++)
                                            @php
                                                $category = old("categories.$index", $agendaCategories[$index] ?? []);
                                                $categoryGoogleCalendarId = $category['google_calendar_id'] ?? '';
                                                $googleCalendarColor = collect($googleCalendars)->firstWhere('id', $categoryGoogleCalendarId)['backgroundColor'] ?? null;
                                                $color = $googleCalendarColor ?? ($category['color'] ?? '#5f948a');
                                            @endphp
                                            <tr>
                                                <td class="py-3 pr-3">
                                                    <input type="hidden" name="categories[{{ $index }}][key]" value="{{ $category['key'] ?? '' }}">
                                                    <input name="categories[{{ $index }}][label]" class="app-field w-full min-w-80" value="{{ $category['label'] ?? '' }}" placeholder="Visita osteopatica">
                                                </td>
                                                <td class="py-3 pr-3">
                                                    <input name="categories[{{ $index }}][color]" type="color" class="h-12 w-20 rounded-xl border border-line bg-white p-1" value="{{ $color }}" data-agenda-category-color="{{ $index }}">
                                                </td>
                                                <td class="py-3 pr-3">
                                                    <select name="categories[{{ $index }}][google_calendar_id]" class="app-field min-w-64" data-agenda-category-calendar="{{ $index }}">
                                                        <option value="">Calendario principale / default</option>
                                                        @foreach ($googleCalendars as $googleCalendar)
                                                            <option value="{{ $googleCalendar['id'] ?? '' }}" @selected($categoryGoogleCalendarId === ($googleCalendar['id'] ?? ''))>
                                                                {{ $googleCalendar['summary'] ?? $googleCalendar['id'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="py-3 pr-3">
                                                    <label class="inline-flex items-center justify-center rounded-xl border border-line bg-white px-4 py-3">
                                                        <input type="checkbox" name="categories[{{ $index }}][sync_patients]" value="1" @checked((bool) ($category['sync_patients'] ?? false)) class="rounded border-gray-300 text-sage focus:ring-sage">
                                                    </label>
                                                </td>
                                                <td class="py-3 pr-3">
                                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold text-white" style="background-color: {{ $color }};" data-agenda-category-preview="{{ $index }}">{{ $category['label'] ?? 'Categoria' }}</span>
                                                </td>
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section class="app-card p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-semibold text-gray-900">Google Calendar</h3>
                                    <p class="mt-1 text-sm text-gray-500">Predisposizione del collegamento. L'attivazione completa richiedera le credenziali Google Cloud.</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $googleCalendarStatus['connected'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $googleCalendarStatus['connected'] ? 'Collegato' : 'Non collegato' }}
                                    </span>
                                    <label class="inline-flex items-center gap-2 rounded-full border border-line bg-white px-3 py-2 text-sm font-bold text-ink">
                                        <input type="checkbox" name="google_calendar_enabled" value="1" @checked((bool) old('google_calendar_enabled', (int) $agendaSettings['google_calendar_enabled'])) class="rounded border-gray-300 text-sage focus:ring-sage">
                                        Attiva collegamento
                                    </label>
                                </div>
                            </div>

                            <div class="mt-5 grid gap-4 md:grid-cols-2">
                                <div>
                                    <x-input-label for="google_calendar_id" value="ID calendario Google" />
                                    <x-text-input id="google_calendar_id" name="google_calendar_id" class="mt-1 block w-full" :value="old('google_calendar_id', $agendaSettings['google_calendar_id'])" placeholder="nome@gmail.com oppure calendar-id" />
                                </div>
                                <div>
                                    <x-input-label for="google_calendar_sync_mode" value="Modalita collegamento" />
                                    <select id="google_calendar_sync_mode" name="google_calendar_sync_mode" class="app-field mt-1 block w-full">
                                        <option value="read" @selected(old('google_calendar_sync_mode', $agendaSettings['google_calendar_sync_mode']) === 'read')>Solo lettura</option>
                                        <option value="write" @selected(old('google_calendar_sync_mode', $agendaSettings['google_calendar_sync_mode']) === 'write')>Solo invio appuntamenti</option>
                                        <option value="two_way" @selected(old('google_calendar_sync_mode', $agendaSettings['google_calendar_sync_mode']) === 'two_way')>Sincronizzazione completa</option>
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="google_calendar_client_id" value="Client ID Google" />
                                    <x-text-input id="google_calendar_client_id" name="google_calendar_client_id" class="mt-1 block w-full" :value="old('google_calendar_client_id', $agendaSettings['google_calendar_client_id'])" placeholder="Usato solo come promemoria: il valore reale e nel file .env" />
                                </div>
                                <div>
                                    <x-input-label for="google_calendar_api_key" value="API key Google" />
                                    <x-text-input id="google_calendar_api_key" name="google_calendar_api_key" class="mt-1 block w-full" :value="old('google_calendar_api_key', $agendaSettings['google_calendar_api_key'])" />
                                </div>
                            </div>

                            <div class="mt-5 rounded-2xl border border-line bg-mist p-5 text-sm text-muted">
                                Per il calendario principale usa <strong>primary</strong> come ID calendario. Il Client Secret resta nel file locale .env e non viene pubblicato su GitHub.
                                @if ($googleCalendarStatus['connected_at'])
                                    <span class="mt-1 block">Ultimo collegamento: {{ $googleCalendarStatus['connected_at'] }}</span>
                                @endif
                            </div>

                            @if ($googleCalendarStatus['connected'])
                                <div class="mt-5 rounded-2xl border border-line bg-white p-5">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <h4 class="text-sm font-bold uppercase text-muted">Calendari da visualizzare</h4>
                                            <p class="mt-1 text-sm text-gray-500">Seleziona quali calendari Google importare e mostrare nell'agenda.</p>
                                        </div>
                                        <button type="submit" form="google-calendar-refresh-form" class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-ink shadow-sm hover:bg-mist">
                                            Aggiorna lista calendari
                                        </button>
                                    </div>

                                    @if ($googleCalendars === [])
                                        <div class="mt-4 rounded-xl border border-dashed border-line bg-mist p-4 text-sm text-muted">
                                            Nessun calendario caricato. Clicca <strong>Aggiorna lista calendari</strong> per leggere i calendari dal tuo account Google.
                                        </div>
                                    @else
                                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                                            @foreach ($googleCalendars as $calendar)
                                                @php
                                                    $calendarId = $calendar['id'] ?? '';
                                                    $calendarColor = $calendar['backgroundColor'] ?? '#64748b';
                                                @endphp
                                                <label class="flex items-start gap-3 rounded-xl border border-line bg-mist/60 p-4 text-sm">
                                                    <input type="checkbox" name="google_calendar_selected_ids[]" value="{{ $calendarId }}" @checked(in_array($calendarId, $selectedGoogleCalendarIds, true)) class="mt-1 rounded border-gray-300 text-sage focus:ring-sage">
                                                    <span class="mt-1 h-3 w-3 shrink-0 rounded-full" style="background-color: {{ $calendarColor }}"></span>
                                                    <span class="min-w-0">
                                                        <span class="block truncate font-bold text-ink">{{ $calendar['summary'] ?? $calendarId }}</span>
                                                        <span class="mt-0.5 block truncate text-xs text-muted">{{ ($calendar['primary'] ?? false) ? 'Calendario principale' : $calendarId }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="mt-5 flex flex-wrap justify-end gap-3">
                                @if ($googleCalendarStatus['configured'])
                                    <a href="{{ route('google.calendar.connect') }}" class="inline-flex items-center rounded-xl border border-sage bg-white px-4 py-2.5 text-sm font-bold text-sage shadow-sm hover:bg-mist">
                                        Collega Google Calendar
                                    </a>
                                    @if ($googleCalendarStatus['connected'])
                                        <select name="sync_year" form="google-calendar-sync-form" class="app-field min-w-28 py-2 pr-9">
                                            @for ($year = now()->year - 2; $year <= now()->year + 2; $year++)
                                                <option value="{{ $year }}" @selected($year === now()->year)>{{ $year }}</option>
                                            @endfor
                                        </select>
                                        <button type="submit" form="google-calendar-sync-form" class="inline-flex items-center rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist">
                                            Sincronizza anno
                                        </button>
                                        <button type="submit" form="google-calendar-disconnect-form" class="inline-flex items-center rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-bold text-red-700 shadow-sm hover:bg-red-50">
                                            Scollega
                                        </button>
                                    @endif
                                @else
                                    <span class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-bold text-red-700">Credenziali Google mancanti nel file .env</span>
                                @endif
                            </div>
                        </section>

                        <div class="flex justify-end">
                            <x-primary-button>Salva impostazioni agenda</x-primary-button>
                        </div>
                    </form>
                    <form id="google-calendar-sync-form" method="POST" action="{{ route('google.calendar.sync') }}" class="hidden">@csrf</form>
                    <form id="google-calendar-disconnect-form" method="POST" action="{{ route('google.calendar.disconnect') }}" class="hidden">@csrf</form>
                    <form id="google-calendar-refresh-form" method="POST" action="{{ route('google.calendar.calendars') }}" class="hidden">@csrf</form>
                    @endif

                    @if ($section === 'sessions')
                    <form method="POST" action="{{ route('settings.sessions.update') }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <section class="app-card p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-semibold text-gray-900">Impostazioni sedute</h3>
                                    <p class="mt-1 text-sm text-gray-500">Tariffario selezionabile nella cartella paziente durante la registrazione delle sedute.</p>
                                </div>
                                <span class="rounded-full bg-mist px-3 py-1 text-xs font-bold uppercase text-sage">Tariffe</span>
                            </div>

                            <div class="mt-5 overflow-x-auto">
                                <table class="w-full min-w-[760px] text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-line text-xs uppercase text-muted">
                                            <th class="pb-3">Attiva</th>
                                            <th class="pb-3">Default</th>
                                            <th class="pb-3">Prestazione</th>
                                            <th class="pb-3">Tariffa</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-line">
                                        @for ($index = 0; $index < 8; $index++)
                                            @php
                                                $rate = old("rates.$index", $sessionRates[$index] ?? []);
                                            @endphp
                                            <tr>
                                                <td class="py-3 pr-3">
                                                    <input type="checkbox" name="rates[{{ $index }}][active]" value="1" @checked((bool) ($rate['active'] ?? false)) class="rounded border-gray-300 text-sage focus:ring-sage">
                                                </td>
                                                <td class="py-3 pr-3">
                                                    <input type="checkbox" name="rates[{{ $index }}][default]" value="1" @checked((bool) ($rate['default'] ?? false)) class="rounded border-gray-300 text-sage focus:ring-sage">
                                                </td>
                                                <td class="py-3 pr-3">
                                                    <input name="rates[{{ $index }}][name]" class="app-field w-full min-w-80" value="{{ $rate['name'] ?? '' }}" placeholder="Es. Seduta di manipolazione osteopatica">
                                                </td>
                                                <td class="py-3 pr-3">
                                                    <input name="rates[{{ $index }}][amount]" type="number" step="0.01" min="0" class="app-field w-32" value="{{ $rate['amount'] ?? '' }}" placeholder="40.00">
                                                </td>
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-5 rounded-2xl border border-line bg-mist p-5 text-sm text-muted">
                                Le tariffe attive compaiono nella tendina della pagina sedute. Se non scegli una tariffa default, verra usata la prima tariffa attiva.
                            </div>

                            <div class="mt-5 flex justify-end">
                                <x-primary-button>Salva impostazioni sedute</x-primary-button>
                            </div>
                        </section>
                    </form>
                    @endif

                    @if ($section === 'accounting')
                    <div class="space-y-6">
                        <section class="app-card p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-semibold text-gray-900">Impostazioni contabilita</h3>
                                    <p class="mt-1 text-sm text-gray-500">Importazione annuale di entrate e spese da file Excel.</p>
                                </div>
                                <span class="rounded-full bg-mist px-3 py-1 text-xs font-bold uppercase text-sage">Contabilita</span>
                            </div>
                        </section>

                        <section class="app-card overflow-hidden">
                            <div class="border-b border-line px-6 py-4" style="background: #d4f0e1;">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-teal-100 bg-white text-sage">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                                    </span>
                                    <div>
                                        <p class="text-xs font-bold uppercase text-muted">Entrate</p>
                                        <h3 class="mt-1 text-lg font-semibold text-gray-900">Importa entrate annuali</h3>
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('settings.accounting.incomes.import') }}" enctype="multipart/form-data" class="space-y-4 p-6">
                                @csrf
                                <input id="accounting_income_year_value" type="hidden" name="year" value="{{ now()->year }}">
                                <div class="gap-4" style="display: grid; grid-template-columns: 235px minmax(0, 1fr) 240px; align-items: end;">
                                    <div>
                                        <x-input-label for="accounting_annual_income_year" value="Anno contabile" />
                                        <select id="accounting_annual_income_year" class="app-field mt-1 block w-full">
                                            @foreach (range((int) now()->year + 1, (int) now()->year - 8) as $year)
                                                <option value="{{ $year }}" @selected($year === (int) now()->year)>{{ $year }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="accounting_annual_income_file" value="File Excel entrate annuali" />
                                        <input id="accounting_annual_income_file" name="annual_incomes_file" type="file" accept=".xlsx,.xls" class="app-field mt-1 block w-full">
                                    </div>
                                    <x-primary-button name="import_kind" value="annual" class="w-full justify-center" onclick="document.getElementById('accounting_income_year_value').value = document.getElementById('accounting_annual_income_year').value">Carica entrate annuali</x-primary-button>
                                </div>

                                <div class="gap-4" style="display: grid; grid-template-columns: 235px minmax(0, 1fr) 240px; align-items: end;">
                                    <div>
                                        <x-input-label for="accounting_gross_income_year" value="Anno contabile" />
                                        <select id="accounting_gross_income_year" class="app-field mt-1 block w-full">
                                            @foreach (range((int) now()->year + 1, (int) now()->year - 8) as $year)
                                                <option value="{{ $year }}" @selected($year === (int) now()->year)>{{ $year }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="accounting_gross_income_file" value="File Excel da fatturare" />
                                        <input id="accounting_gross_income_file" name="gross_incomes_file" type="file" accept=".xlsx,.xls" class="app-field mt-1 block w-full">
                                    </div>
                                    <x-primary-button name="import_kind" value="gross" class="w-full justify-center" onclick="document.getElementById('accounting_income_year_value').value = document.getElementById('accounting_gross_income_year').value">Carica da fatturare</x-primary-button>
                                </div>

                                <input type="hidden" name="replace_existing" value="0">
                                <label class="flex items-center gap-2 text-sm font-semibold text-muted">
                                    <input type="checkbox" name="replace_existing" value="1" checked class="rounded border-line text-sage focus:ring-sage">
                                    Sostituisci i valori manuali gia importati per l'anno selezionato
                                </label>
                            </form>
                        </section>

                        <section class="app-card overflow-hidden">
                            <div class="border-b border-line px-6 py-4" style="background: #ffd7d7;">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-rose-100 bg-white text-rose-700">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
                                    </span>
                                    <div>
                                        <p class="text-xs font-bold uppercase text-muted">Spese</p>
                                        <h3 class="mt-1 text-lg font-semibold text-gray-900">Importa spese annuali</h3>
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('settings.accounting.expenses.import') }}" enctype="multipart/form-data" class="grid gap-4 p-6 md:grid-cols-[160px_1fr_auto] md:items-end">
                                @csrf
                                <div>
                                    <x-input-label for="accounting_expense_year" value="Anno contabile" />
                                    <select id="accounting_expense_year" name="year" class="app-field mt-1 block w-full">
                                        @foreach (range((int) now()->year + 1, (int) now()->year - 8) as $year)
                                            <option value="{{ $year }}" @selected($year === (int) now()->year)>{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="accounting_expense_file" value="File Excel spese" />
                                    <input id="accounting_expense_file" name="expenses_file" type="file" accept=".xlsx,.xls" class="app-field mt-1 block w-full" required>
                                </div>
                                <x-primary-button>Carica spese</x-primary-button>
                                <input type="hidden" name="replace_existing" value="0">
                                <label class="flex items-center gap-2 text-sm font-semibold text-muted md:col-span-3">
                                    <input type="checkbox" name="replace_existing" value="1" checked class="rounded border-line text-sage focus:ring-sage">
                                    Sostituisci le spese gia importate per l'anno selezionato
                                </label>
                            </form>
                        </section>

                        <section class="app-card overflow-hidden">
                            <div class="border-b border-line px-6 py-4" style="background: #f5bf8e;">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-orange-100 bg-white text-orange-700">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6"/><path d="M9 18h6"/><path d="M9 12h2"/></svg>
                                    </span>
                                    <div>
                                        <p class="text-xs font-bold uppercase text-muted">Imposte</p>
                                        <h3 class="mt-1 text-lg font-semibold text-gray-900">Parametri di calcolo modificabili</h3>
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('settings.accounting.update') }}" class="space-y-5 p-6">
                                @csrf
                                @method('PATCH')
                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <div>
                                        <x-input-label for="accounting_tax_regime" value="Regime fiscale" />
                                        <x-text-input id="accounting_tax_regime" name="accounting_tax_regime" class="mt-1 block w-full" :value="old('accounting_tax_regime', $accountingTaxSettings['accounting_tax_regime'])" />
                                    </div>
                                    <div>
                                        <x-input-label for="accounting_flat_rate_costs_rate" value="Forfait spese (%)" />
                                        <x-text-input id="accounting_flat_rate_costs_rate" name="accounting_flat_rate_costs_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('accounting_flat_rate_costs_rate', $accountingTaxSettings['accounting_flat_rate_costs_rate'])" />
                                    </div>
                                    <div>
                                        <x-input-label for="accounting_tax_rate" value="Tasse (%)" />
                                        <x-text-input id="accounting_tax_rate" name="accounting_tax_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('accounting_tax_rate', $accountingTaxSettings['accounting_tax_rate'])" />
                                    </div>
                                    <div>
                                        <x-input-label for="accounting_inps_rate" value="INPS (%)" />
                                        <x-text-input id="accounting_inps_rate" name="accounting_inps_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('accounting_inps_rate', $accountingTaxSettings['accounting_inps_rate'])" />
                                    </div>
                                    <div>
                                        <x-input-label for="accounting_november_tax_advance_rate" value="Acconto novembre tasse (%)" />
                                        <x-text-input id="accounting_november_tax_advance_rate" name="accounting_november_tax_advance_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('accounting_november_tax_advance_rate', $accountingTaxSettings['accounting_november_tax_advance_rate'])" />
                                    </div>
                                    <div>
                                        <x-input-label for="accounting_november_inps_advance_rate" value="Acconto novembre INPS (%)" />
                                        <x-text-input id="accounting_november_inps_advance_rate" name="accounting_november_inps_advance_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('accounting_november_inps_advance_rate', $accountingTaxSettings['accounting_november_inps_advance_rate'])" />
                                    </div>
                                    <div>
                                        <x-input-label for="accounting_november_inps_installments" value="Rate acconto INPS" />
                                        <x-text-input id="accounting_november_inps_installments" name="accounting_november_inps_installments" type="number" step="1" min="1" max="12" class="mt-1 block w-full" :value="old('accounting_november_inps_installments', $accountingTaxSettings['accounting_november_inps_installments'])" />
                                    </div>
                                </div>

                                <div class="rounded-xl border border-orange-100 bg-orange-50 px-4 py-3 text-sm font-semibold text-muted">
                                    Questi valori alimentano il riepilogo imposte nella pagina Contabilita.
                                </div>

                                <div class="flex justify-end">
                                    <x-primary-button>Salva impostazioni imposte</x-primary-button>
                                </div>
                            </form>
                        </section>
                    </div>
                    @endif

                    @if ($section === 'privacy')
                    <section class="app-card overflow-hidden">
                        <div class="border-b border-line bg-cyan-50 px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-cyan-100 bg-white text-cyan-700">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m9 15 2 2 4-4"/></svg>
                                </span>
                                <div>
                                    <p class="text-xs font-bold uppercase text-muted">Privacy</p>
                                    <h3 class="mt-1 text-lg font-semibold text-gray-900">Documento originale consenso</h3>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('settings.privacy.update') }}" enctype="multipart/form-data" class="space-y-5 p-6">
                            @csrf
                            @method('PATCH')

                            <div class="rounded-xl border border-cyan-100 bg-cyan-50 px-4 py-3 text-sm text-muted">
                                Questo testo viene usato per creare il PDF privacy nella cartella del paziente. I campi tra doppie graffe vengono compilati automaticamente dal gestionale.
                            </div>

                            <div>
                                <x-input-label for="privacy_consent_template" value="Testo documento privacy" />
                                <textarea id="privacy_consent_template" name="privacy_consent_template" rows="28" class="mt-1 block w-full rounded-xl border-line bg-white px-4 py-3 font-mono text-sm text-gray-900 shadow-sm focus:border-brand focus:ring-brand">{{ old('privacy_consent_template', $privacyTemplate) }}</textarea>
                                <p class="mt-2 text-xs text-muted">
                                    Segnaposto disponibili:
                                    <code>@{{paziente_nome_cognome}}</code>,
                                    <code>@{{paziente_luogo_nascita}}</code>,
                                    <code>@{{paziente_data_nascita}}</code>,
                                    <code>@{{paziente_codice_fiscale}}</code>,
                                    <code>@{{paziente_residenza}}</code>,
                                    <code>@{{data_consenso}}</code>.
                                </p>
                            </div>

                            <div class="grid items-end gap-4 md:grid-cols-[1fr_auto]">
                                <div>
                                    <x-input-label for="privacy_template_file" value="Sostituisci documento con file Word o testo" />
                                    <input id="privacy_template_file" name="privacy_template_file" type="file" accept=".txt,.docx" class="mt-1 block w-full rounded-xl border border-line bg-white px-4 py-3 text-sm shadow-sm">
                                    <p class="mt-2 text-xs text-muted">Caricando un file, il testo sopra viene sostituito dal contenuto del documento.</p>
                                </div>
                                <x-primary-button class="justify-center px-8">Salva documento privacy</x-primary-button>
                            </div>

                            @if ($privacyTemplateUpdatedAt)
                                <p class="text-xs text-muted">Ultima modifica: {{ \Illuminate\Support\Carbon::parse($privacyTemplateUpdatedAt)->format('d/m/Y H:i') }}</p>
                            @endif
                        </form>
                    </section>
                    @endif

                    @if ($section === 'backup')
                    <section class="app-card overflow-hidden">
                        <div class="border-b border-line bg-emerald-50 px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-emerald-100 bg-white text-emerald-700">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                                </span>
                                <div>
                                    <p class="text-xs font-bold uppercase text-muted">Backup</p>
                                    <h3 class="mt-1 text-lg font-semibold text-gray-900">Backup ed esportazioni</h3>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('settings.backup.update') }}" class="space-y-6 p-6">
                            @csrf
                            @method('PATCH')

                            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-muted">
                                Queste impostazioni preparano il gestionale al salvataggio periodico di database, documenti generati e file caricati. L'esecuzione automatica verra collegata al comando di backup quando decidiamo la destinazione definitiva.
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <label class="flex items-center gap-3 rounded-xl border border-line bg-white px-4 py-3 text-sm font-semibold text-gray-800 shadow-sm">
                                    <input type="checkbox" name="backup_enabled" value="1" class="rounded border-line text-brand focus:ring-brand" @checked(old('backup_enabled', $backupSettings['backup_enabled']) == '1')>
                                    Backup attivo
                                </label>
                                <div>
                                    <x-input-label for="backup_frequency" value="Frequenza" />
                                    <select id="backup_frequency" name="backup_frequency" class="app-field mt-1 block w-full">
                                        <option value="daily" @selected(old('backup_frequency', $backupSettings['backup_frequency']) === 'daily')>Giornaliera</option>
                                        <option value="weekly" @selected(old('backup_frequency', $backupSettings['backup_frequency']) === 'weekly')>Settimanale</option>
                                        <option value="monthly" @selected(old('backup_frequency', $backupSettings['backup_frequency']) === 'monthly')>Mensile</option>
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="backup_time" value="Ora esecuzione" />
                                    <x-text-input id="backup_time" name="backup_time" type="time" class="mt-1 block w-full" :value="old('backup_time', $backupSettings['backup_time'])" />
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <div>
                                    <x-input-label for="backup_retention_days" value="Conservazione backup (giorni)" />
                                    <x-text-input id="backup_retention_days" name="backup_retention_days" type="number" min="1" max="3650" step="1" class="mt-1 block w-full" :value="old('backup_retention_days', $backupSettings['backup_retention_days'])" />
                                </div>
                                <div>
                                    <x-input-label for="backup_destination" value="Destinazione" />
                                    <select id="backup_destination" name="backup_destination" class="app-field mt-1 block w-full">
                                        <option value="local" @selected(old('backup_destination', $backupSettings['backup_destination']) === 'local')>Cartella locale</option>
                                        <option value="external" @selected(old('backup_destination', $backupSettings['backup_destination']) === 'external')>Disco esterno / NAS</option>
                                        <option value="cloud" @selected(old('backup_destination', $backupSettings['backup_destination']) === 'cloud')>Cloud</option>
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="backup_path" value="Percorso cartella backup" />
                                    <x-text-input id="backup_path" name="backup_path" class="mt-1 block w-full" :value="old('backup_path', $backupSettings['backup_path'])" placeholder="storage/app/backups" />
                                </div>
                            </div>

                            <section class="rounded-xl border border-line bg-white p-4">
                                <h4 class="text-sm font-bold uppercase text-muted">Contenuto backup</h4>
                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    <label class="flex items-center gap-3 rounded-xl border border-line px-4 py-3 text-sm font-semibold text-gray-800">
                                        <input type="checkbox" name="backup_database" value="1" class="rounded border-line text-brand focus:ring-brand" @checked(old('backup_database', $backupSettings['backup_database']) == '1')>
                                        Database gestionale
                                    </label>
                                    <label class="flex items-center gap-3 rounded-xl border border-line px-4 py-3 text-sm font-semibold text-gray-800">
                                        <input type="checkbox" name="backup_uploaded_files" value="1" class="rounded border-line text-brand focus:ring-brand" @checked(old('backup_uploaded_files', $backupSettings['backup_uploaded_files']) == '1')>
                                        File caricati
                                    </label>
                                    <label class="flex items-center gap-3 rounded-xl border border-line px-4 py-3 text-sm font-semibold text-gray-800">
                                        <input type="checkbox" name="backup_generated_documents" value="1" class="rounded border-line text-brand focus:ring-brand" @checked(old('backup_generated_documents', $backupSettings['backup_generated_documents']) == '1')>
                                        Documenti generati, fatture e consensi
                                    </label>
                                    <label class="flex items-center gap-3 rounded-xl border border-line px-4 py-3 text-sm font-semibold text-gray-800">
                                        <input type="checkbox" name="backup_logs" value="1" class="rounded border-line text-brand focus:ring-brand" @checked(old('backup_logs', $backupSettings['backup_logs']) == '1')>
                                        Log tecnici
                                    </label>
                                </div>
                            </section>

                            <section class="rounded-xl border border-line bg-white p-4">
                                <h4 class="text-sm font-bold uppercase text-muted">Sicurezza e notifiche</h4>
                                <div class="mt-4 grid gap-4 md:grid-cols-[260px_1fr]">
                                    <label class="flex items-center gap-3 rounded-xl border border-line px-4 py-3 text-sm font-semibold text-gray-800">
                                        <input type="checkbox" name="backup_encrypt" value="1" class="rounded border-line text-brand focus:ring-brand" @checked(old('backup_encrypt', $backupSettings['backup_encrypt']) == '1')>
                                        Crittografia backup
                                    </label>
                                    <div>
                                        <x-input-label for="backup_notify_email" value="Email notifica esito backup" />
                                        <x-text-input id="backup_notify_email" name="backup_notify_email" type="email" class="mt-1 block w-full" :value="old('backup_notify_email', $backupSettings['backup_notify_email'])" placeholder="nome@email.it" />
                                    </div>
                                </div>
                            </section>

                            <div>
                                <x-input-label for="backup_notes" value="Note operative" />
                                <textarea id="backup_notes" name="backup_notes" rows="4" class="mt-1 block w-full rounded-xl border-line bg-white px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-brand focus:ring-brand" placeholder="Esempio: controllare ogni venerdi che il file sia stato copiato anche su disco esterno.">{{ old('backup_notes', $backupSettings['backup_notes']) }}</textarea>
                            </div>

                            <div class="flex justify-end">
                                <x-primary-button>Salva impostazioni backup</x-primary-button>
                            </div>
                        </form>
                    </section>
                    @endif
                </div>

                <aside class="space-y-6">
                    <section class="app-card p-6">
                        <h3 class="font-semibold text-gray-900">Area amministrativa</h3>
                        <div class="mt-4 space-y-3">
                            <a href="{{ route('settings.edit') }}" class="block rounded-md border px-4 py-3 text-sm font-medium {{ $section === 'studio' ? 'border-gray-200 bg-gray-50 text-gray-900' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">Dati studio</a>
                            <a href="{{ route('settings.patients') }}" class="block rounded-md border px-4 py-3 text-sm font-medium {{ $section === 'patients' ? 'border-gray-200 bg-gray-50 text-gray-900' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">Impostazioni pazienti</a>
                            <a href="{{ route('settings.sessions') }}" class="block rounded-md border px-4 py-3 text-sm font-medium {{ $section === 'sessions' ? 'border-gray-200 bg-gray-50 text-gray-900' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">Impostazioni sedute</a>
                            <a href="{{ route('settings.agenda') }}" class="block rounded-md border px-4 py-3 text-sm font-medium {{ $section === 'agenda' ? 'border-gray-200 bg-gray-50 text-gray-900' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">Impostazioni agenda</a>
                            <a href="{{ route('settings.invoices') }}" class="block rounded-md border px-4 py-3 text-sm font-medium {{ $section === 'invoices' ? 'border-gray-200 bg-gray-50 text-gray-900' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">Impostazioni fatture</a>
                            <a href="{{ route('settings.accounting') }}" class="block rounded-md border px-4 py-3 text-sm font-medium {{ $section === 'accounting' ? 'border-gray-200 bg-gray-50 text-gray-900' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">Impostazioni contabilita</a>
                            <a href="{{ route('settings.privacy') }}" class="block rounded-md border px-4 py-3 text-sm font-medium {{ $section === 'privacy' ? 'border-gray-200 bg-gray-50 text-gray-900' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">Impostazioni privacy</a>
                            <a href="{{ route('settings.users') }}" class="block rounded-md border px-4 py-3 text-sm font-medium {{ $section === 'users' ? 'border-gray-200 bg-gray-50 text-gray-900' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">Utenti e password</a>
                            <a href="{{ route('settings.backup') }}" class="block rounded-md border px-4 py-3 text-sm font-medium {{ $section === 'backup' ? 'border-gray-200 bg-gray-50 text-gray-900' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">Backup ed esportazioni</a>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
    <script>
        (() => {
            const calendarColors = @json(collect($googleCalendars)->mapWithKeys(fn ($calendar) => [$calendar['id'] ?? '' => $calendar['backgroundColor'] ?? '#64748b'])->filter(fn ($color, $id) => filled($id)));

            document.querySelectorAll('[data-agenda-category-calendar]').forEach((select) => {
                select.addEventListener('change', () => {
                    const index = select.dataset.agendaCategoryCalendar;
                    const color = calendarColors[select.value];

                    if (!color) {
                        return;
                    }

                    const colorInput = document.querySelector(`[data-agenda-category-color="${index}"]`);
                    const preview = document.querySelector(`[data-agenda-category-preview="${index}"]`);

                    if (colorInput) {
                        colorInput.value = color;
                    }

                    if (preview) {
                        preview.style.backgroundColor = color;
                    }
                });
            });
        })();
    </script>
</x-app-layout>
