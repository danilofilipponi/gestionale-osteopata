<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cartella paziente: {{ $patient->full_name }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $patient->phone ?: 'Telefono non inserito' }} - {{ $patient->email ?: 'Email non inserita' }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('patients.edit', $patient) }}" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800">Modifica anagrafica</a>
                <a href="{{ route('patients.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Torna ai pazienti</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 md:grid-cols-4">
                <a href="{{ route('patients.show', $patient) }}" class="rounded-lg p-4 text-sm font-medium shadow-sm {{ $section === 'anagrafica' ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Anagrafica</a>
                <a href="{{ route('patients.sessions.index', $patient) }}" class="rounded-lg p-4 text-sm font-medium shadow-sm {{ $section === 'sedute' ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Storico sedute</a>
                <a href="{{ route('patients.invoices.index', $patient) }}" class="rounded-lg p-4 text-sm font-medium shadow-sm {{ $section === 'fatture' ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Storico fatture</a>
                <a href="{{ route('patients.privacy.index', $patient) }}" class="rounded-lg p-4 text-sm font-medium shadow-sm {{ $section === 'privacy' ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Privacy e consenso</a>
            </div>

            @if ($section === 'anagrafica')
            <section id="anagrafica" class="rounded-lg bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">Anagrafica</h3>
                        <p class="mt-1 text-sm text-gray-500">Dati identificativi e informazioni cliniche iniziali.</p>
                    </div>
                    <a href="{{ route('patients.edit', $patient) }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Modifica dati</a>
                </div>

                <dl class="mt-5 grid gap-4 md:grid-cols-3">
                    <div class="rounded-md border border-gray-100 p-4">
                        <dt class="text-xs font-medium uppercase text-gray-500">Nome completo</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $patient->full_name }}</dd>
                    </div>
                    <div class="rounded-md border border-gray-100 p-4">
                        <dt class="text-xs font-medium uppercase text-gray-500">Data di nascita</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $patient->birth_date?->format('d/m/Y') ?: 'Non inserita' }}{{ $patient->age ? ' - '.$patient->age.' anni' : '' }}</dd>
                    </div>
                    <div class="rounded-md border border-gray-100 p-4">
                        <dt class="text-xs font-medium uppercase text-gray-500">Sesso</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $patient->gender ?: 'Non inserito' }}</dd>
                    </div>
                    <div class="rounded-md border border-gray-100 p-4">
                        <dt class="text-xs font-medium uppercase text-gray-500">Luogo di nascita</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $patient->birth_place ?: 'Non inserito' }}</dd>
                    </div>
                    <div class="rounded-md border border-gray-100 p-4">
                        <dt class="text-xs font-medium uppercase text-gray-500">Codice fiscale</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $patient->fiscal_code ?: 'Non inserito' }}</dd>
                    </div>
                    <div class="rounded-md border border-gray-100 p-4">
                        <dt class="text-xs font-medium uppercase text-gray-500">Telefono</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $patient->phone ?: 'Non inserito' }}</dd>
                    </div>
                    <div class="rounded-md border border-gray-100 p-4">
                        <dt class="text-xs font-medium uppercase text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $patient->email ?: 'Non inserita' }}</dd>
                    </div>
                    <div class="rounded-md border border-gray-100 p-4">
                        <dt class="text-xs font-medium uppercase text-gray-500">Professione</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $patient->profession ?: 'Non inserita' }}</dd>
                    </div>
                    <div class="rounded-md border border-gray-100 p-4">
                        <dt class="text-xs font-medium uppercase text-gray-500">Indirizzo</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ collect([$patient->address, $patient->postal_code, $patient->city, $patient->province])->filter()->join(' ') ?: 'Non inserito' }}</dd>
                    </div>
                </dl>

                @if ($patient->notes)
                    <div class="mt-4 rounded-md border border-gray-100 p-4">
                        <p class="text-xs font-medium uppercase text-gray-500">Note anagrafiche</p>
                        <p class="mt-2 text-sm text-gray-700">{{ $patient->notes }}</p>
                    </div>
                @endif

                <div class="mt-8 border-t border-gray-100 pt-6">
                    <h4 class="font-semibold text-gray-900">Dati clinici iniziali</h4>
                    <form method="POST" action="{{ route('patients.medical-record.store', $patient) }}" class="mt-4 grid gap-4 md:grid-cols-2">
                        @csrf
                        @foreach ([
                            'reason_for_visit' => 'Motivo della visita',
                            'symptoms_started_at' => 'Data inizio sintomi',
                            'pain_description' => 'Descrizione dolore/problema',
                            'irradiation' => 'Irradiazione',
                            'exams' => 'Esami/indagini',
                            'previous_treatments' => 'Trattamenti precedenti',
                            'traumas' => 'Traumi',
                            'surgeries' => 'Interventi chirurgici',
                            'visceral_issues' => 'Problematiche viscerali',
                            'prosthesis_and_devices' => 'Protesi, plantari, bite, ortodonzia',
                            'family_history' => 'Anamnesi familiare',
                            'birth_history' => 'Anamnesi parto',
                            'lifestyle' => 'Abitudini di vita',
                            'sport' => 'Sport',
                            'physical_sphere' => 'Sfera fisica',
                            'psychological_sphere' => 'Sfera psichica',
                            'medications' => 'Farmaci',
                            'clinical_tests' => 'Test clinici',
                            'anamnesis' => 'Anamnesi',
                            'diagnostic_notes' => 'Valutazione',
                            'treatment_plan' => 'Piano di trattamento',
                            'contraindications' => 'Controindicazioni'
                        ] as $field => $label)
                            <div class="{{ in_array($field, ['contraindications', 'pain_description', 'clinical_tests'], true) ? 'md:col-span-2' : '' }}">
                                <x-input-label :for="$field" :value="$label" />
                                @if ($field === 'symptoms_started_at')
                                    <x-text-input id="{{ $field }}" name="{{ $field }}" type="date" class="mt-1 block w-full" :value="old($field, $patient->medicalRecord?->{$field}?->toDateString())" />
                                @else
                                    <textarea id="{{ $field }}" name="{{ $field }}" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">{{ old($field, $patient->medicalRecord?->{$field}) }}</textarea>
                                @endif
                            </div>
                        @endforeach
                        <div class="md:col-span-2">
                            <x-primary-button>Salva dati clinici</x-primary-button>
                        </div>
                    </form>
                </div>
            </section>
            @endif

            @if ($section === 'sedute')
            <section id="sedute" class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-gray-900">Storico delle sedute</h3>
                <form method="POST" action="{{ route('patients.sessions.store', $patient) }}" class="mt-4 space-y-4 rounded-md border border-gray-100 p-4">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-input-label for="session_date" value="Data" />
                            <x-text-input id="session_date" name="session_date" type="date" class="mt-1 block w-full" :value="old('session_date', now()->toDateString())" required />
                        </div>
                        <div>
                            <x-input-label for="title" value="Titolo" />
                            <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title', 'Seduta osteopatica')" required />
                        </div>
                    </div>
                    <textarea name="objective" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="Obiettivo">{{ old('objective') }}</textarea>
                    <textarea name="treatment" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="Trattamento eseguito">{{ old('treatment') }}</textarea>
                    <div>
                        <x-input-label for="pain_level" value="Dolore 1-10" />
                        <x-text-input id="pain_level" name="pain_level" type="number" min="1" max="10" class="mt-1 block w-full" :value="old('pain_level')" />
                    </div>
                    <textarea name="outcome" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="Esito e indicazioni">{{ old('outcome') }}</textarea>
                    <textarea name="notes" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="Note">{{ old('notes') }}</textarea>
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <x-text-input name="fee" type="number" step="0.01" min="0" class="w-40" placeholder="Importo" :value="old('fee')" />
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="paid" value="1" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                                Pagata
                            </label>
                        </div>
                        <x-primary-button>Registra seduta</x-primary-button>
                    </div>
                </form>

                <div class="mt-5 divide-y divide-gray-100">
                    @forelse ($patient->treatmentSessions as $session)
                        <div class="py-4">
                            <div class="flex items-center justify-between gap-4">
                                <p class="font-medium text-gray-900">{{ $session->title }}</p>
                                <p class="text-sm text-gray-500">{{ $session->session_date->format('d/m/Y') }}</p>
                            </div>
                            <p class="mt-2 text-sm text-gray-600">{{ $session->treatment ?: 'Nessun dettaglio trattamento inserito.' }}</p>
                            <p class="mt-2 text-sm text-gray-500">Dolore: {{ $session->pain_level ?: 'n.d.' }}/10 - EUR {{ number_format($session->fee ?? 0, 2, ',', '.') }} - {{ $session->paid ? 'Pagata' : 'Da saldare' }}</p>

                            <details class="mt-3">
                                <summary class="cursor-pointer text-sm font-medium text-gray-700">Modifica seduta</summary>
                                <form method="POST" action="{{ route('patients.sessions.update', [$patient, $session]) }}" class="mt-4 space-y-3 rounded-md border border-gray-200 p-4">
                                    @csrf
                                    @method('PATCH')
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <x-text-input name="session_date" type="date" :value="$session->session_date->toDateString()" required />
                                        <x-text-input name="title" :value="$session->title" required />
                                    </div>
                                    <textarea name="objective" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="Obiettivo">{{ $session->objective }}</textarea>
                                    <textarea name="treatment" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="Trattamento eseguito">{{ $session->treatment }}</textarea>
                                    <x-text-input name="pain_level" type="number" min="1" max="10" placeholder="Dolore 1-10" :value="$session->pain_level" />
                                    <textarea name="outcome" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="Esito e indicazioni">{{ $session->outcome }}</textarea>
                                    <textarea name="notes" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="Note">{{ $session->notes }}</textarea>
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex items-center gap-4">
                                            <x-text-input name="fee" type="number" step="0.01" min="0" class="w-40" placeholder="Importo" :value="$session->fee" />
                                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="paid" value="1" @checked($session->paid) class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                                                Pagata
                                            </label>
                                        </div>
                                        <x-primary-button>Salva seduta</x-primary-button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('patients.sessions.destroy', [$patient, $session]) }}" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-sm font-medium text-red-700 hover:text-red-900" onclick="return confirm('Eliminare questa seduta?')">Elimina seduta</button>
                                </form>
                            </details>
                        </div>
                    @empty
                        <p class="py-6 text-sm text-gray-500">Nessuna seduta registrata.</p>
                    @endforelse
                </div>
            </section>
            @endif

            @if ($section === 'fatture')
            <section id="fatture" class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-gray-900">Storico delle fatture emesse</h3>
                <form method="POST" action="{{ route('patients.invoices.store', $patient) }}" class="mt-4 space-y-4 rounded-md border border-gray-100 p-4">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-4">
                        <x-text-input name="number" placeholder="Numero fattura" :value="old('number')" />
                        <x-text-input name="issued_at" type="date" :value="old('issued_at', now()->toDateString())" required />
                        <x-text-input name="service" placeholder="Prestazione" :value="old('service', 'Seduta osteopatica')" />
                        <x-text-input name="amount" type="number" step="0.01" min="0" placeholder="Importo" :value="old('amount')" required />
                        <x-text-input name="payment_method" placeholder="Pagamento" :value="old('payment_method')" />
                        <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            <option value="draft">Bozza</option>
                            <option value="sent">Inviata</option>
                            <option value="paid">Pagata</option>
                            <option value="cancelled">Annullata</option>
                        </select>
                    </div>
                    <textarea name="description" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="Descrizione">{{ old('description') }}</textarea>
                    <x-primary-button>Registra fattura</x-primary-button>
                </form>

                <div class="mt-5 divide-y divide-gray-100">
                    @forelse ($patient->invoices as $invoice)
                        <div class="py-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $invoice->number ?: 'Fattura senza numero' }}</p>
                                    <p class="text-sm text-gray-500">{{ $invoice->issued_at->format('d/m/Y') }} - {{ $invoice->service ?: 'Prestazione non indicata' }} - {{ $invoice->status }}</p>
                                </div>
                                <p class="font-semibold text-gray-900">EUR {{ number_format($invoice->amount, 2, ',', '.') }}</p>
                            </div>

                            <details class="mt-3">
                                <summary class="cursor-pointer text-sm font-medium text-gray-700">Modifica fattura</summary>
                                <form method="POST" action="{{ route('patients.invoices.update', [$patient, $invoice]) }}" class="mt-4 space-y-3 rounded-md border border-gray-200 p-4">
                                    @csrf
                                    @method('PATCH')
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <x-text-input name="number" placeholder="Numero fattura" :value="$invoice->number" />
                                        <x-text-input name="issued_at" type="date" :value="$invoice->issued_at->toDateString()" required />
                                        <x-text-input name="service" placeholder="Prestazione" :value="$invoice->service" />
                                        <x-text-input name="amount" type="number" step="0.01" min="0" placeholder="Importo" :value="$invoice->amount" required />
                                        <x-text-input name="payment_method" placeholder="Pagamento" :value="$invoice->payment_method" />
                                        <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                            <option value="draft" @selected($invoice->status === 'draft')>Bozza</option>
                                            <option value="sent" @selected($invoice->status === 'sent')>Inviata</option>
                                            <option value="paid" @selected($invoice->status === 'paid')>Pagata</option>
                                            <option value="cancelled" @selected($invoice->status === 'cancelled')>Annullata</option>
                                        </select>
                                    </div>
                                    <textarea name="description" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="Descrizione">{{ $invoice->description }}</textarea>
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <button form="delete-invoice-{{ $invoice->id }}" class="text-sm font-medium text-red-700 hover:text-red-900" onclick="return confirm('Eliminare questa fattura?')">Elimina fattura</button>
                                        <x-primary-button>Salva fattura</x-primary-button>
                                    </div>
                                </form>
                                <form id="delete-invoice-{{ $invoice->id }}" method="POST" action="{{ route('patients.invoices.destroy', [$patient, $invoice]) }}" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </details>
                        </div>
                    @empty
                        <p class="py-6 text-sm text-gray-500">Nessuna fattura registrata.</p>
                    @endforelse
                </div>
            </section>
            @endif

            @if ($section === 'privacy')
            <section id="privacy" class="rounded-lg bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">Privacy e consenso</h3>
                        <p class="mt-1 text-sm text-gray-500">Registro del consenso privacy e trattamento dati sanitari.</p>
                    </div>
                    @if ($patient->privacyConsent?->privacy_policy_accepted && $patient->privacyConsent?->health_data_processing_accepted)
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700">Consenso presente</span>
                    @else
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-medium text-amber-700">Consenso da completare</span>
                    @endif
                </div>

                <form method="POST" action="{{ route('patients.privacy-consent.store', $patient) }}" class="mt-5 grid gap-4 md:grid-cols-2">
                    @csrf
                    <label class="flex items-start gap-3 rounded-md border border-gray-100 p-4 text-sm text-gray-700">
                        <input type="checkbox" name="privacy_policy_accepted" value="1" @checked(old('privacy_policy_accepted', $patient->privacyConsent?->privacy_policy_accepted)) class="mt-1 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        <span>Consenso informativa privacy</span>
                    </label>
                    <label class="flex items-start gap-3 rounded-md border border-gray-100 p-4 text-sm text-gray-700">
                        <input type="checkbox" name="health_data_processing_accepted" value="1" @checked(old('health_data_processing_accepted', $patient->privacyConsent?->health_data_processing_accepted)) class="mt-1 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        <span>Consenso trattamento dati sanitari</span>
                    </label>
                    <label class="flex items-start gap-3 rounded-md border border-gray-100 p-4 text-sm text-gray-700 md:col-span-2">
                        <input type="checkbox" name="marketing_accepted" value="1" @checked(old('marketing_accepted', $patient->privacyConsent?->marketing_accepted)) class="mt-1 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        <span>Consenso comunicazioni informative/promozionali</span>
                    </label>
                    <div>
                        <x-input-label for="signed_at" value="Data consenso" />
                        <x-text-input id="signed_at" name="signed_at" type="date" class="mt-1 block w-full" :value="old('signed_at', $patient->privacyConsent?->signed_at?->toDateString())" />
                    </div>
                    <div>
                        <x-input-label for="signature_method" value="Metodo firma" />
                        <select id="signature_method" name="signature_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            @foreach (['cartaceo' => 'Cartaceo', 'digitale' => 'Digitale', 'verbale' => 'Verbale'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('signature_method', $patient->privacyConsent?->signature_method) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="document_version" value="Versione documento" />
                        <x-text-input id="document_version" name="document_version" class="mt-1 block w-full" :value="old('document_version', $patient->privacyConsent?->document_version)" placeholder="es. privacy-v1" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="privacy_notes" value="Note privacy" />
                        <textarea id="privacy_notes" name="notes" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">{{ old('notes', $patient->privacyConsent?->notes) }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <x-primary-button>Salva consenso</x-primary-button>
                    </div>
                </form>
            </section>
            @endif
        </div>
    </div>
</x-app-layout>
