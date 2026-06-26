<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cartella paziente: {{ $patient->full_name }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $patient->phone ?: 'Telefono non inserito' }} - {{ $patient->email ?: 'Email non inserita' }}</p>
            </div>
            <div class="flex w-full gap-2 sm:w-auto sm:flex-wrap sm:gap-3">
                <a href="{{ route('patients.edit', $patient) }}" class="flex-1 rounded-xl bg-sage px-3 py-2.5 text-center text-sm font-bold text-white shadow-sm transition hover:bg-[#4f7f75] sm:flex-none sm:px-4">Modifica anagrafica</a>
                <a href="{{ route('patients.index') }}" class="flex-1 rounded-xl border border-line bg-white px-3 py-2.5 text-center text-sm font-bold text-ink shadow-sm hover:bg-mist sm:flex-none sm:px-4">Torna ai pazienti</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="app-section space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 md:mx-0 md:grid md:grid-cols-5 md:gap-4 md:overflow-visible md:px-0 md:pb-0">
                <a href="{{ route('patients.show', $patient) }}" class="shrink-0 rounded-xl border px-4 py-3 text-sm font-bold shadow-card md:rounded-2xl md:p-4 {{ $section === 'anagrafica' ? 'border-sage bg-sage text-white' : 'border-line bg-white text-muted hover:bg-mist hover:text-ink' }}">Anagrafica</a>
                <a href="{{ route('patients.anamnesis.index', $patient) }}" class="shrink-0 rounded-xl border px-4 py-3 text-sm font-bold shadow-card md:rounded-2xl md:p-4 {{ $section === 'anamnesi' ? 'border-sage bg-sage text-white' : 'border-line bg-white text-muted hover:bg-mist hover:text-ink' }}">Anamnesi</a>
                <a href="{{ route('patients.sessions.index', $patient) }}" class="shrink-0 rounded-xl border px-4 py-3 text-sm font-bold shadow-card md:rounded-2xl md:p-4 {{ $section === 'sedute' ? 'border-sage bg-sage text-white' : 'border-line bg-white text-muted hover:bg-mist hover:text-ink' }}">Storico sedute</a>
                <a href="{{ route('patients.invoices.index', $patient) }}" class="shrink-0 rounded-xl border px-4 py-3 text-sm font-bold shadow-card md:rounded-2xl md:p-4 {{ $section === 'fatture' ? 'border-sage bg-sage text-white' : 'border-line bg-white text-muted hover:bg-mist hover:text-ink' }}">Storico fatture</a>
                <a href="{{ route('patients.privacy.index', $patient) }}" class="shrink-0 rounded-xl border px-4 py-3 text-sm font-bold shadow-card md:rounded-2xl md:p-4 {{ $section === 'privacy' ? 'border-sage bg-sage text-white' : 'border-line bg-white text-muted hover:bg-mist hover:text-ink' }}">Privacy e consenso</a>
            </div>

            @if ($section === 'anagrafica')
            @php
                $icon = function (string $name): string {
                    $paths = [
                        'user' => '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/>',
                        'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/>',
                        'heart' => '<path d="M19 14c1.5-1.5 3-3.3 3-5.5A5.5 5.5 0 0 0 12 5a5.5 5.5 0 0 0-10 3.5c0 2.2 1.5 4 3 5.5l7 7Z"/>',
                        'map-pin' => '<path d="M20 10c0 4.5-8 12-8 12S4 14.5 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
                        'id-card' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M15 8h2"/><path d="M15 12h3"/><path d="M7 16h4"/>',
                        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.7 2.6a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.5-1.2a2 2 0 0 1 2.1-.5c.8.3 1.7.6 2.6.7a2 2 0 0 1 1.7 2Z"/>',
                        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
                        'briefcase' => '<path d="M10 6V5a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v1"/><rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 12h18"/>',
                        'home' => '<path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>',
                        'hash' => '<path d="M4 9h16"/><path d="M4 15h16"/><path d="M10 3 8 21"/><path d="m16 3-2 18"/>',
                        'building' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 21v-4h6v4"/><path d="M8 7h.01"/><path d="M12 7h.01"/><path d="M16 7h.01"/><path d="M8 11h.01"/><path d="M12 11h.01"/><path d="M16 11h.01"/>',
                        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h6"/>',
                    ];

                    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name] ?? $paths['file']).'</svg>';
                };

                $missing = 'Non inserito';
                $birthLabel = $patient->birth_date?->format('d/m/Y') ?: 'Non inserita';
                if ($patient->age) {
                    $birthLabel .= ' - '.$patient->age.' anni';
                }

                $personalItems = [
                    ['user', 'ID paziente', $patient->legacy_patient_id ?: $patient->id],
                    ['calendar', 'Data di nascita', $birthLabel],
                    ['heart', 'Sesso', $patient->gender ?: $missing],
                    ['map-pin', 'Luogo di nascita', $patient->birth_place ?: $missing],
                    ['id-card', 'Codice fiscale', $patient->fiscal_code ?: $missing],
                    ['briefcase', 'Professione', $patient->profession ?: $missing],
                ];

                $contactItems = [
                    ['phone', 'Telefono', $patient->phone ?: $missing],
                    ['mail', 'Email', $patient->email ?: 'Non inserita'],
                    ['mail', 'PEC', $patient->pec ?: 'Non inserita'],
                    ['file', 'ID paese', $patient->country_id ?: 'IT'],
                ];

                $addressItems = [
                    ['home', 'Indirizzo', $patient->address ?: $missing],
                    ['hash', 'Civico', $patient->street_number ?: $missing],
                    ['building', 'Citta', $patient->city ?: $missing],
                    ['map-pin', 'Provincia', $patient->province ?: $missing],
                    ['file', 'CAP', $patient->postal_code ?: $missing],
                ];
            @endphp
            <section id="anagrafica" class="app-card p-4 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-5">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full border-2 border-teal-100 bg-teal-50 text-teal-600 shadow-card sm:h-20 sm:w-20">
                            {!! $icon('user') !!}
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Anagrafica paziente</p>
                            <h3 class="mt-1 text-xl font-bold text-sage sm:text-2xl">{{ $patient->full_name }}</h3>
                            <p class="mt-1 text-sm text-muted">ID {{ $patient->legacy_patient_id ?: $patient->id }}{{ $patient->customer_type ? ' - '.$patient->customer_type : '' }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @if (filled($patient->privacyConsent?->signature_data))
                            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">Privacy firmata</span>
                        @else
                            <span class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700">Privacy non firmata</span>
                        @endif
                        <a href="{{ route('patients.edit', $patient) }}" class="rounded-md border-2 border-sage/30 bg-white px-4 py-2 text-sm font-bold text-sage hover:bg-mist">Modifica dati</a>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-lg border-2 border-line bg-white p-4">
                        <h4 class="text-xs font-bold uppercase text-muted">Dati personali</h4>
                        <dl class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($personalItems as [$itemIcon, $label, $value])
                                <div class="flex items-start gap-3 rounded-md border border-line bg-mist/30 px-3 py-2">
                                    <span class="mt-0.5 text-sage">{!! $icon($itemIcon) !!}</span>
                                    <div class="min-w-0">
                                        <dt class="text-[11px] font-bold uppercase text-muted">{{ $label }}</dt>
                                        <dd class="truncate text-sm font-semibold text-ink" title="{{ $value }}">{{ $value }}</dd>
                                    </div>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    <div class="rounded-lg border-2 border-line bg-white p-4">
                        <h4 class="text-xs font-bold uppercase text-muted">Contatti</h4>
                        <dl class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($contactItems as [$itemIcon, $label, $value])
                                <div class="flex items-start gap-3 rounded-md border border-line bg-mist/30 px-3 py-2">
                                    <span class="mt-0.5 text-sage">{!! $icon($itemIcon) !!}</span>
                                    <div class="min-w-0">
                                        <dt class="text-[11px] font-bold uppercase text-muted">{{ $label }}</dt>
                                        <dd class="truncate text-sm font-semibold text-ink" title="{{ $value }}">{{ $value }}</dd>
                                    </div>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border-2 border-line bg-white p-4">
                    <h4 class="text-xs font-bold uppercase text-muted">Residenza</h4>
                    <dl class="mt-3 grid gap-2 md:grid-cols-5">
                        @foreach ($addressItems as [$itemIcon, $label, $value])
                            <div class="flex items-start gap-3 rounded-md border border-line bg-mist/30 px-3 py-2">
                                <span class="mt-0.5 text-sage">{!! $icon($itemIcon) !!}</span>
                                <div class="min-w-0">
                                    <dt class="text-[11px] font-bold uppercase text-muted">{{ $label }}</dt>
                                    <dd class="truncate text-sm font-semibold text-ink" title="{{ $value }}">{{ $value }}</dd>
                                </div>
                            </div>
                        @endforeach
                    </dl>
                </div>

                @if ($patient->notes)
                    <div class="mt-4 rounded-lg border-2 border-line bg-white p-4">
                        <p class="text-xs font-bold uppercase text-muted">Note anagrafiche</p>
                        <p class="mt-2 text-sm text-ink">{{ $patient->notes }}</p>
                    </div>
                @endif

                <div class="mt-4 rounded-lg border-2 border-line bg-white p-4">
                    <p class="text-xs font-bold uppercase text-muted">Dati fiscali ed esportazione</p>
                    <dl class="mt-3 grid gap-2 md:grid-cols-5">
                        @foreach ([
                            ['file', 'Tipo cliente', $patient->customer_type ?: 'Privato'],
                            ['file', 'SDI o PEC', $patient->telematic_address ?: '0000000'],
                            ['id-card', 'Partita IVA', $patient->vat_number ?: 'Non inserita'],
                            ['building', 'Denominazione', $patient->business_name ?: 'Non inserita'],
                            ['file', 'Codice EORI', $patient->eori_code ?: 'Non inserito'],
                        ] as [$itemIcon, $label, $value])
                            <div class="flex items-start gap-3 rounded-md border border-line bg-mist/30 px-3 py-2">
                                <span class="mt-0.5 text-sage">{!! $icon($itemIcon) !!}</span>
                                <div class="min-w-0">
                                    <dt class="text-[11px] font-bold uppercase text-muted">{{ $label }}</dt>
                                    <dd class="truncate text-sm font-semibold text-ink" title="{{ $value }}">{{ $value }}</dd>
                                </div>
                            </div>
                        @endforeach
                    </dl>
                </div>

            </section>
            @endif

            @if ($section === 'anamnesi')
            @php
                $anamnesisIcon = function (string $name): string {
                    $paths = [
                        'clipboard' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3"/><path d="M8 13h8"/><path d="M8 17h5"/>',
                        'search' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 12h4"/><path d="M8 16h2"/><circle cx="14.5" cy="15.5" r="2.5"/><path d="m16.5 17.5 2 2"/>',
                        'heart' => '<path d="M19 14c1.5-1.5 3-3.3 3-5.5A5.5 5.5 0 0 0 12 5a5.5 5.5 0 0 0-10 3.5c0 2.2 1.5 4 3 5.5l7 7Z"/>',
                        'tooth' => '<path d="M12 5.5C10 3.5 6.5 3.8 5 6c-2.5 3.7.4 12.8 2.5 13.5 1.4.5 1.7-4.5 4.5-4.5s3.1 5 4.5 4.5C18.6 18.8 21.5 9.7 19 6c-1.5-2.2-5-2.5-7-.5Z"/>',
                        'home' => '<path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>',
                        'activity' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
                    ];

                    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name] ?? $paths['clipboard']).'</svg>';
                };

                $anamnesisGroups = [
                    'Consulto' => [
                        'icon' => 'clipboard',
                        'fields' => [
                            'reason_for_visit' => 'Motivo del consulto',
                            'symptoms_started_at' => 'Inizio sintomi',
                            'pain_description' => 'Dolore e irradiazione',
                        ],
                    ],
                    'Indagini e trattamenti precedenti' => [
                        'icon' => 'search',
                        'fields' => [
                            'exams' => 'Indagini eseguite',
                            'previous_treatments' => 'Trattamenti precedenti',
                        ],
                    ],
                    'Storia clinica' => [
                        'icon' => 'heart',
                        'fields' => [
                            'traumas' => 'Traumi',
                            'surgeries' => 'Chirurgie',
                            'visceral_issues' => 'Problematiche viscerali',
                        ],
                    ],
                    'Dispositivi e odontoiatria' => [
                        'icon' => 'tooth',
                        'fields' => [
                            'prosthesis_and_devices' => 'Protesi, plantari, bite',
                            'orthodontics' => 'Ortodonzia',
                        ],
                    ],
                    'Anamnesi familiare e stile di vita' => [
                        'icon' => 'home',
                        'fields' => [
                            'family_history' => 'Famiglia / parto',
                            'lifestyle' => 'Abitudini / sport',
                        ],
                    ],
                    'Area generale' => [
                        'icon' => 'activity',
                        'fields' => [
                            'physical_sphere' => 'Sfera fisica e psichica',
                            'medications' => 'Farmaci',
                        ],
                    ],
                ];
            @endphp
            <section id="anamnesi" class="app-card p-4 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full border-2 border-teal-100 bg-teal-50 text-teal-600 shadow-card">
                            {!! $anamnesisIcon('clipboard') !!}
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Cartella clinica</p>
                            <h3 class="mt-1 text-xl font-bold text-sage">Anamnesi</h3>
                        </div>
                    </div>
                    <x-primary-button form="anamnesis-form">Salva anamnesi</x-primary-button>
                </div>

                <form id="anamnesis-form" method="POST" action="{{ route('patients.medical-record.store', $patient) }}" class="mt-5" data-unsaved-form>
                    @csrf
                    <div class="grid gap-4 xl:grid-cols-2">
                        @foreach ($anamnesisGroups as $groupTitle => $group)
                            <div class="rounded-lg border-2 border-line bg-white p-4 shadow-sm {{ ($group['wide'] ?? false) ? 'xl:col-span-2' : '' }}">
                                <div class="flex items-center gap-3 border-b border-line pb-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-teal-100 bg-teal-50 text-sage">
                                        {!! $anamnesisIcon($group['icon']) !!}
                                    </span>
                                    <h4 class="text-xs font-bold uppercase text-muted">{{ $groupTitle }}</h4>
                                </div>
                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    @foreach ($group['fields'] as $field => $label)
                                        @php
                                            $isWide = $field === 'pain_description';
                                            $rows = $groupTitle === 'Storia clinica' || $isWide ? 2 : 1;
                                        @endphp
                                        <div class="{{ $isWide ? 'md:col-span-2' : '' }}">
                                            <label for="{{ $field }}" class="text-[11px] font-bold uppercase text-muted">{{ $label }}</label>
                                            <textarea id="{{ $field }}" name="{{ $field }}" rows="{{ $rows }}" class="app-field mt-1 block w-full resize-y text-sm leading-relaxed">{{ old($field, $patient->medicalRecord?->{$field}) }}</textarea>
                                        </div>
                                    @endforeach
                                </div>
                        </div>
                    @endforeach
                </div>

                    <div class="mt-4 flex justify-end">
                        <x-primary-button>Salva anamnesi</x-primary-button>
                    </div>
                    <input type="hidden" name="irradiation" value="{{ old('irradiation', $patient->medicalRecord?->irradiation) }}">
                    <input type="hidden" name="birth_history" value="{{ old('birth_history', $patient->medicalRecord?->birth_history) }}">
                    <input type="hidden" name="sport" value="{{ old('sport', $patient->medicalRecord?->sport) }}">
                    <input type="hidden" name="psychological_sphere" value="{{ old('psychological_sphere', $patient->medicalRecord?->psychological_sphere) }}">
                    <input type="hidden" name="anamnesis" value="{{ old('anamnesis', $patient->medicalRecord?->anamnesis) }}">
                    <input type="hidden" name="clinical_tests" value="{{ old('clinical_tests', $patient->medicalRecord?->clinical_tests) }}">
                    <input type="hidden" name="diagnostic_notes" value="{{ old('diagnostic_notes', $patient->medicalRecord?->diagnostic_notes) }}">
                    <input type="hidden" name="treatment_plan" value="{{ old('treatment_plan', $patient->medicalRecord?->treatment_plan) }}">
                    <input type="hidden" name="contraindications" value="{{ old('contraindications', $patient->medicalRecord?->contraindications) }}">
                </form>

                <div class="fixed inset-x-4 bottom-4 z-50 hidden rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 shadow-xl md:left-auto md:w-[440px]" data-unsaved-warning="anamnesis-form">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-bold">Anamnesi non salvata</p>
                            <p class="mt-1 text-amber-900/80">Salva l'anamnesi prima di uscire dalla pagina.</p>
                        </div>
                        <button type="submit" form="anamnesis-form" class="rounded-xl bg-sage px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-[#4f7f75]">Salva anamnesi</button>
                    </div>
                </div>

                @push('scripts')
                    <script>
                        (() => {
                            const form = document.getElementById('anamnesis-form');
                            const warning = document.querySelector('[data-unsaved-warning="anamnesis-form"]');

                            if (! form || ! warning) return;

                            let dirty = false;
                            let submitting = false;

                            const showWarning = () => {
                                if (submitting) return;
                                dirty = true;
                                warning.classList.remove('hidden');
                            };

                            form.addEventListener('input', showWarning);
                            form.addEventListener('change', showWarning);
                            form.addEventListener('submit', () => {
                                submitting = true;
                                dirty = false;
                                warning.classList.add('hidden');
                            });

                            window.addEventListener('beforeunload', (event) => {
                                if (! dirty || submitting) return;
                                event.preventDefault();
                                event.returnValue = '';
                            });

                            document.addEventListener('click', (event) => {
                                const link = event.target.closest('a[href]');
                                if (! link || ! dirty || submitting) return;
                                if (link.target && link.target !== '_self') return;
                                if (link.href === window.location.href || link.href.startsWith('javascript:')) return;

                                if (! confirm('Ci sono modifiche non salvate. Vuoi uscire senza salvare?')) {
                                    event.preventDefault();
                                }
                            });
                        })();
                    </script>
                @endpush
            </section>
            @endif

            @if ($section === 'sedute')
            @php
                $sessionsByYear = $patient->treatmentSessions->groupBy(fn ($session) => $session->session_date?->format('Y') ?: 'Senza data');
                $sessionRates = \App\Support\TreatmentSessionDefaults::activeRates();
                $defaultSessionRate = \App\Support\TreatmentSessionDefaults::defaultRate();
                $patientLastSessionRate = $patient->treatmentSessions
                    ->filter(fn ($session) => filled($session->fee))
                    ->sortByDesc(fn ($session) => optional($session->updated_at)->timestamp ?? optional($session->created_at)->timestamp ?? 0)
                    ->first();
                $preferredSessionRate = $patientLastSessionRate
                    ? [
                        'name' => $patientLastSessionRate->title ?: ($defaultSessionRate['name'] ?? 'Seduta osteopatica'),
                        'amount' => (float) $patientLastSessionRate->fee,
                    ]
                    : $defaultSessionRate;
                $selectedSessionTitle = old('title', $preferredSessionRate['name'] ?? 'Seduta osteopatica');
                $selectedSessionFee = (float) old('fee', $preferredSessionRate['amount'] ?? 0);
                $hasPreferredRateInList = collect($sessionRates)->contains(fn ($rate) => (float) ($rate['amount'] ?? 0) === $selectedSessionFee
                    && ($rate['name'] ?? '') === $selectedSessionTitle);
            @endphp
            <section id="sedute" class="app-card p-4 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full border-2 border-teal-100 bg-teal-50 text-teal-600 shadow-card">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Trattamenti</p>
                            <h3 class="mt-1 text-xl font-bold text-sage">Storico delle sedute</h3>
                        </div>
                    </div>
                    <span class="rounded-full border border-line bg-mist px-3 py-1.5 text-xs font-bold text-muted">{{ $patient->treatmentSessions->count() }} sedute</span>
                </div>

                <form method="POST" action="{{ route('patients.sessions.store', $patient) }}" class="mt-5 rounded-lg border-2 border-line bg-white p-4 shadow-sm">
                    @csrf
                    <input type="hidden" name="title" value="{{ $selectedSessionTitle }}" data-session-title>
                    <input type="hidden" name="objective" value="{{ old('objective') }}">
                    <input type="hidden" name="outcome" value="{{ old('outcome') }}">
                    <input type="hidden" name="fee" value="{{ $selectedSessionFee }}" data-session-fee>
                    <div class="flex w-full flex-col items-stretch gap-4 lg:flex-row">
                        <div class="grid min-w-0 grid-cols-2 gap-2 lg:w-[276px] lg:flex-none lg:grid-cols-[140px_128px]">
                            <div class="min-w-0 rounded-md border border-line bg-mist/40 p-3">
                                <div class="flex items-center gap-2 text-xs font-bold uppercase text-muted">
                                    <svg class="h-4 w-4 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M8 2v4" />
                                        <path d="M16 2v4" />
                                        <rect width="18" height="18" x="3" y="4" rx="2" />
                                        <path d="M3 10h18" />
                                    </svg>
                                    Data seduta
                                </div>
                                <x-text-input id="session_date" name="session_date" type="date" class="mt-2 block w-full text-sm font-bold" :value="old('session_date', now()->toDateString())" required />
                            </div>
                            <div class="min-w-0 rounded-md border border-line bg-mist/40 p-3">
                                <label for="session_rate" class="flex items-center gap-2 text-xs font-bold uppercase text-muted">
                                    <svg class="h-4 w-4 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 1v22" />
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6" />
                                    </svg>
                                    Tariffa
                                </label>
                                <select id="session_rate" class="app-field mt-2 block w-full text-sm font-bold" onchange="const option = this.options[this.selectedIndex]; const form = this.closest('form'); form.querySelector('[data-session-title]').value = option.value; form.querySelector('[data-session-fee]').value = option.dataset.amount || 0;">
                                    @if (! $hasPreferredRateInList)
                                        <option value="{{ $selectedSessionTitle }}" data-amount="{{ $selectedSessionFee }}" selected>
                                            € {{ number_format($selectedSessionFee, 2, ',', '.') }}
                                        </option>
                                    @endif
                                    @foreach ($sessionRates as $rate)
                                        <option value="{{ $rate['name'] }}" data-amount="{{ $rate['amount'] ?? 0 }}" @selected($selectedSessionTitle === ($rate['name'] ?? '') && $selectedSessionFee === (float) ($rate['amount'] ?? 0))>
                                            € {{ number_format((float) ($rate['amount'] ?? 0), 2, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div data-pain-slider class="min-w-0 flex-1 rounded-md border border-line bg-white p-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 text-xs font-bold uppercase text-muted">
                                    <svg class="h-4 w-4 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M8 15s1.5-2 4-2 4 2 4 2" />
                                        <path d="M9 9h.01" />
                                        <path d="M15 9h.01" />
                                    </svg>
                                    Scala analogica visiva del dolore
                                </div>
                                <span class="rounded-full border border-line bg-mist px-3 py-1 text-xs font-black text-sage"><span data-pain-value>{{ old('pain_level', 0) }}</span>/10</span>
                            </div>
                            <div class="mt-4" style="display: grid; grid-template-columns: 28px minmax(0, 1fr) 28px; align-items: center; gap: 12px;">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full border border-line bg-mist text-sage" aria-hidden="true">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M8 14s1.5 2 4 2 4-2 4-2" /><path d="M9 9h.01" /><path d="M15 9h.01" /></svg>
                                </span>
                                <input id="pain_level" name="pain_level" type="range" min="0" max="10" step="1" value="{{ old('pain_level', 0) }}" class="h-2 w-full cursor-pointer accent-sage" oninput="this.closest('[data-pain-slider]').querySelector('[data-pain-value]').textContent = this.value">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full border border-line bg-mist text-red-700" aria-hidden="true">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M8 16s1.5-2 4-2 4 2 4 2" /><path d="M9 9h.01" /><path d="M15 9h.01" /></svg>
                                </span>
                            </div>
                            <div class="mt-2" style="display: grid; grid-template-columns: 28px minmax(0, 1fr) 28px; gap: 12px;">
                                <div></div>
                                <div class="text-xs font-bold leading-none text-muted" style="display: flex; justify-content: space-between;">
                                    @for ($painTick = 0; $painTick <= 10; $painTick++)
                                        <span>{{ $painTick }}</span>
                                    @endfor
                                </div>
                                <div></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div>
                            <label for="notes" class="flex items-center gap-2 text-xs font-bold uppercase text-muted">
                                <svg class="h-4 w-4 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 4h16v16H4z" />
                                    <path d="M8 8h8" />
                                    <path d="M8 12h8" />
                                    <path d="M8 16h5" />
                                </svg>
                                Motivo del consulto
                            </label>
                            <textarea id="notes" name="notes" rows="2" class="app-field mt-2 block w-full text-sm" placeholder="Motivo del consulto">{{ old('notes') }}</textarea>
                        </div>
                        <div>
                            <label for="treatment" class="flex items-center gap-2 text-xs font-bold uppercase text-muted">
                                <svg class="h-4 w-4 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 2v20" />
                                    <path d="M2 12h20" />
                                    <path d="m19 5-4 4" />
                                    <path d="m5 19 4-4" />
                                </svg>
                                Tipo di trattamento
                            </label>
                            <textarea id="treatment" name="treatment" rows="3" class="app-field mt-2 block w-full text-sm" placeholder="Descrivi il trattamento eseguito">{{ old('treatment') }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <x-primary-button>Registra seduta</x-primary-button>
                    </div>
                </form>

                <div class="mt-5 space-y-3">
                    @forelse ($sessionsByYear as $year => $sessions)
                        <details class="rounded-lg border-2 border-line bg-white p-4" {{ $loop->first ? 'open' : '' }}>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                                <span class="inline-flex items-center gap-2 text-lg font-bold text-ink">
                                    <span class="text-sage">&#9656;</span>
                                    {{ $year }}
                                </span>
                                <span class="rounded-full bg-mist px-3 py-1 text-xs font-bold text-muted">{{ $sessions->count() }} sedute</span>
                            </summary>
                            <div class="mt-4 space-y-3">
                                @foreach ($sessions as $session)
                                    <div class="rounded-md border-2 border-line bg-white p-4 shadow-sm">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p class="font-bold text-ink">{{ $session->title }}</p>
                                                <div class="mt-2 flex flex-wrap gap-3 text-sm text-muted">
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <svg class="h-4 w-4 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M8 2v4" />
                                                            <path d="M16 2v4" />
                                                            <rect width="18" height="18" x="3" y="4" rx="2" />
                                                            <path d="M3 10h18" />
                                                        </svg>
                                                        {{ $session->session_date?->format('d/m/Y') ?: 'Data non inserita' }}
                                                    </span>
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <svg class="h-4 w-4 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <circle cx="12" cy="12" r="10" />
                                                            <path d="M8 15s1.5-2 4-2 4 2 4 2" />
                                                            <path d="M9 9h.01" />
                                                            <path d="M15 9h.01" />
                                                        </svg>
                                                        Dolore {{ $session->pain_level ?? 'n.d.' }}/10
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap gap-2 text-xs font-bold">
                                                <span class="rounded-full border border-line bg-mist px-2.5 py-1 text-muted">{{ $session->paid ? 'Pagata' : 'Da saldare' }}</span>
                                            </div>
                                        </div>
                                        <div class="mt-3 grid gap-3 md:grid-cols-[320px_1fr]">
                                            <div class="rounded-md border border-line bg-mist/30 p-3">
                                                <p class="flex items-center gap-2 text-xs font-bold uppercase text-muted">
                                                    <svg class="h-4 w-4 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M4 4h16v16H4z" />
                                                        <path d="M8 8h8" />
                                                        <path d="M8 12h8" />
                                                    </svg>
                                                    Motivo del consulto
                                                </p>
                                                <p class="mt-1 text-sm text-ink">{{ $session->notes ?: 'Nessun motivo inserito.' }}</p>
                                            </div>
                                            <div class="rounded-md border border-line bg-mist/30 p-3">
                                                <p class="flex items-center gap-2 text-xs font-bold uppercase text-muted">
                                                    <svg class="h-4 w-4 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M12 2v20" />
                                                        <path d="M2 12h20" />
                                                    </svg>
                                                    Tipo di trattamento
                                                </p>
                                                <p class="mt-1 text-sm text-ink">{{ $session->treatment ?: 'Nessun dettaglio trattamento inserito.' }}</p>
                                            </div>
                                        </div>

                            <details class="mt-3">
                                <summary class="cursor-pointer text-sm font-bold text-sage">Modifica seduta</summary>
                                <form method="POST" action="{{ route('patients.sessions.update', [$patient, $session]) }}" class="mt-4 rounded-md border border-line bg-white p-4">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="title" value="{{ $session->title ?: ($defaultSessionRate['name'] ?? 'Seduta osteopatica') }}" data-session-title>
                                    <input type="hidden" name="objective" value="{{ $session->objective }}">
                                    <input type="hidden" name="outcome" value="{{ $session->outcome }}">
                                    <input type="hidden" name="fee" value="{{ $session->fee ?? ($defaultSessionRate['amount'] ?? 0) }}" data-session-fee>
                                    <input type="hidden" name="paid" value="{{ $session->paid ? 1 : 0 }}">
                                    <div class="flex w-full flex-col items-stretch gap-4 lg:flex-row">
                                        <div class="grid min-w-0 grid-cols-2 gap-2 lg:w-[276px] lg:flex-none lg:grid-cols-[140px_128px]">
                                            <div class="min-w-0">
                                                <x-input-label value="Data seduta" />
                                                <x-text-input name="session_date" type="date" class="mt-1 block w-full" :value="$session->session_date?->toDateString()" required />
                                            </div>
                                            <div class="min-w-0">
                                                <x-input-label value="Tariffa" />
                                                <select class="app-field mt-1 block w-full" onchange="const option = this.options[this.selectedIndex]; const form = this.closest('form'); form.querySelector('[data-session-title]').value = option.value; form.querySelector('[data-session-fee]').value = option.dataset.amount || 0;">
                                                    @foreach ($sessionRates as $rate)
                                                        <option value="{{ $rate['name'] }}" data-amount="{{ $rate['amount'] ?? 0 }}" @selected(($session->title ?: '') === ($rate['name'] ?? ''))>
                                                            € {{ number_format((float) ($rate['amount'] ?? 0), 2, ',', '.') }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div data-pain-slider class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-3">
                                                <x-input-label value="Scala dolore" />
                                                <span class="rounded-full border border-line bg-mist px-3 py-1 text-xs font-black text-sage"><span data-pain-value>{{ $session->pain_level ?? 0 }}</span>/10</span>
                                            </div>
                                            <div class="mt-3" style="display: grid; grid-template-columns: 28px minmax(0, 1fr) 28px; align-items: center; gap: 12px;">
                                                <span class="flex h-7 w-7 items-center justify-center rounded-full border border-line bg-mist text-sage" aria-hidden="true">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M8 14s1.5 2 4 2 4-2 4-2" /><path d="M9 9h.01" /><path d="M15 9h.01" /></svg>
                                                </span>
                                                <input name="pain_level" type="range" min="0" max="10" step="1" value="{{ $session->pain_level ?? 0 }}" class="h-2 w-full cursor-pointer accent-sage" oninput="this.closest('[data-pain-slider]').querySelector('[data-pain-value]').textContent = this.value">
                                                <span class="flex h-7 w-7 items-center justify-center rounded-full border border-line bg-mist text-red-700" aria-hidden="true">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M8 16s1.5-2 4-2 4 2 4 2" /><path d="M9 9h.01" /><path d="M15 9h.01" /></svg>
                                                </span>
                                            </div>
                                            <div class="mt-2" style="display: grid; grid-template-columns: 28px minmax(0, 1fr) 28px; gap: 12px;">
                                                <div></div>
                                                <div class="text-xs font-bold leading-none text-muted" style="display: flex; justify-content: space-between;">
                                                    @for ($painTick = 0; $painTick <= 10; $painTick++)
                                                        <span>{{ $painTick }}</span>
                                                    @endfor
                                                </div>
                                                <div></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4 space-y-3">
                                        <div>
                                            <x-input-label value="Motivo del consulto" />
                                            <textarea name="notes" rows="2" class="app-field mt-1 block w-full text-sm" placeholder="Motivo del consulto">{{ $session->notes }}</textarea>
                                        </div>
                                        <div>
                                            <x-input-label value="Tipo di trattamento" />
                                            <textarea name="treatment" rows="3" class="app-field mt-1 block w-full text-sm" placeholder="Descrivi il trattamento eseguito">{{ $session->treatment }}</textarea>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex justify-end">
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
                                @endforeach
                            </div>
                        </details>
                    @empty
                        <p class="rounded-lg border-2 border-line bg-white p-4 text-sm text-muted">Nessuna seduta registrata.</p>
                    @endforelse
                </div>
            </section>
            @endif

            @if ($section === 'fatture')
            @php
                $invoicesByYear = $patient->invoices->groupBy(fn ($invoice) => $invoice->issued_at?->format('Y') ?: 'Senza data');
                $invoiceServices = \App\Support\InvoiceDefaults::services();
                $defaultInvoiceService = \App\Support\InvoiceDefaults::defaultService();
                $invoiceSettings = \App\Support\InvoiceDefaults::settings();
                $paymentMethods = \App\Support\InvoiceDefaults::paymentMethods();
                $nextInvoice = \App\Support\InvoiceDefaults::nextNumber();
                $invoiceStatusLabels = [
                    'draft' => 'Bozza',
                    'sent' => 'Emessa',
                    'paid' => 'Pagata',
                    'cancelled' => 'Annullata',
                ];
                $paymentLabel = fn (?string $code) => $code && isset($paymentMethods[$code])
                    ? \Illuminate\Support\Str::after($paymentMethods[$code], ' - ')
                    : 'Pagamento n.d.';
                $invoiceIcon = function (string $name): string {
                    $paths = [
                        'hash' => '<path d="M4 9h16"/><path d="M4 15h16"/><path d="M10 3 8 21"/><path d="m16 3-2 18"/>',
                        'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/>',
                        'service' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h6"/>',
                        'euro' => '<path d="M4 10h12"/><path d="M4 14h10"/><path d="M18 6.2A7 7 0 1 0 18 17.8"/>',
                        'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/>',
                        'status' => '<path d="M20 6 9 17l-5-5"/>',
                        'send' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
                    ];

                    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name] ?? $paths['service']).'</svg>';
                };
                $defaultLineAmount = (float) ($defaultInvoiceService['amount'] ?? 0);
                $defaultSocialSecurityRate = (float) ($defaultInvoiceService['social_security_rate'] ?? $invoiceSettings['invoice_social_security_rate']);
                $defaultSocialSecurity = $defaultLineAmount * $defaultSocialSecurityRate / 100;
                $defaultTaxable = $defaultLineAmount + $defaultSocialSecurity;
                $defaultStamp = (bool) ($defaultInvoiceService['stamp_duty'] ?? false) && $defaultTaxable > (float) $invoiceSettings['invoice_stamp_threshold']
                    ? (float) $invoiceSettings['invoice_stamp_amount']
                    : 0;
                $nextInvoiceId = ((int) \App\Models\Invoice::max('id')) + 1;
                $defaultInvoiceDescription = 'IDFattura: '.$nextInvoiceId
                    .' | Importo: '.number_format($defaultLineAmount, 2, '.', '')
                    .' | Inps: '.number_format($defaultSocialSecurity, 2, '.', '')
                    .' | Bollo: '.number_format($defaultStamp, 2, '.', '');
            @endphp
            <section id="fatture" class="app-card p-4 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full border-2 border-teal-100 bg-teal-50 text-teal-600 shadow-card">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h6"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Amministrazione</p>
                            <h3 class="mt-1 text-xl font-bold text-sage">Storico delle fatture emesse</h3>
                        </div>
                    </div>
                    <span class="rounded-full border border-line bg-mist px-3 py-1.5 text-xs font-bold text-muted">{{ $patient->invoices->count() }} fatture</span>
                </div>

                <form method="POST" action="{{ route('patients.invoices.store', $patient) }}" class="mt-5 space-y-3 rounded-lg p-4 shadow-sm" style="background-color: #dff3ef; border: 2px solid #7fb6ad;">
                    @csrf
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-teal-200 bg-teal-50/80 px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-teal-100 text-sage">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                                    <path d="M14 2v6h6" />
                                    <path d="M12 18v-6" />
                                    <path d="M9 15h6" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-xs font-bold uppercase text-muted">Nuova fattura</p>
                            </div>
                        </div>
                    </div>
                    <div class="grid gap-3 md:grid-cols-7">
                        <div>
                            <x-input-label for="new_invoice_number" value="Numero" />
                            <x-text-input id="new_invoice_number" name="number" class="mt-1 block w-full" :value="old('number', $nextInvoice['number'])" />
                            <input type="hidden" name="auto_number_reference" value="{{ $nextInvoice['number'] }}">
                        </div>
                        <div>
                            <x-input-label for="new_invoice_issued_at" value="Data" />
                            <x-text-input id="new_invoice_issued_at" name="issued_at" type="date" class="mt-1 block w-full" :value="old('issued_at', now()->toDateString())" required />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="new_invoice_service" value="Servizio" />
                            <input
                                id="new_invoice_service"
                                name="service"
                                list="invoice-service-options"
                                class="app-field mt-1 block w-full"
                                value="{{ old('service', $defaultInvoiceService['name']) }}"
                                data-invoice-service-input
                                data-services='@json($invoiceServices)'
                                data-settings='@json($invoiceSettings)'
                                data-invoice-id="{{ $nextInvoiceId }}"
                            >
                            <datalist id="invoice-service-options">
                                @foreach ($invoiceServices as $service)
                                    <option value="{{ $service['name'] }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <x-input-label for="new_invoice_unit_amount" value="Prezzo unitario" />
                            <x-text-input id="new_invoice_unit_amount" name="unit_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('unit_amount', $defaultInvoiceService['amount'] ?? '')" data-invoice-unit-amount required />
                            <input type="hidden" name="amount" value="{{ old('amount', $defaultInvoiceService['amount'] ?? '') }}" data-invoice-total-amount>
                            <input type="hidden" name="line_amount" value="{{ old('line_amount', $defaultInvoiceService['amount'] ?? '') }}" data-invoice-line-amount>
                        </div>
                        <div>
                            <x-input-label for="new_invoice_quantity" value="Quantita" />
                            <x-text-input id="new_invoice_quantity" name="quantity" type="number" step="1" min="1" class="mt-1 block w-full" :value="old('quantity', 1)" data-invoice-quantity required />
                        </div>
                        <div>
                            <x-input-label for="new_invoice_line_total" value="Totale prestazione" />
                            <div id="new_invoice_line_total" class="mt-1 rounded-xl border border-line bg-mist px-3.5 py-3 text-sm font-bold text-ink" data-invoice-line-total-display>€ {{ number_format((float) ($defaultInvoiceService['amount'] ?? 0), 2, ',', '.') }}</div>
                        </div>
                        <div>
                            <x-input-label for="new_invoice_status" value="Stato fattura" />
                            <select id="new_invoice_status" name="status" class="app-field mt-1 block w-full">
                                @foreach ($invoiceStatusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'paid') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid gap-3 md:grid-cols-3">
                        <div>
                            <x-input-label for="new_invoice_payment_method" value="Metodo di pagamento" />
                            <select id="new_invoice_payment_method" name="payment_method" class="app-field mt-1 block w-full">
                                @foreach ($paymentMethods as $code => $label)
                                    <option value="{{ $code }}" @selected(old('payment_method', $invoiceSettings['invoice_payment_method']) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="new_invoice_payment_date" value="Data pagamento" />
                            <x-text-input id="new_invoice_payment_date" name="payment_date" type="date" class="mt-1 block w-full" :value="old('payment_date', old('issued_at', now()->toDateString()))" data-invoice-payment-date />
                        </div>
                        <div>
                            <x-input-label for="new_invoice_payment_terms" value="Condizioni pagamento" />
                            <select id="new_invoice_payment_terms" class="app-field mt-1 block w-full" disabled>
                                <option>Pagamento completo</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid gap-3 md:grid-cols-4">
                        <div>
                            <x-input-label value="Aliquota cassa" />
                            <div class="mt-1 rounded-xl border border-line bg-white px-3.5 py-3 text-sm font-bold text-ink" data-invoice-social-security-display>
                                {{ number_format((float) ($defaultInvoiceService['social_security_rate'] ?? $invoiceSettings['invoice_social_security_rate']), 2, ',', '.') }}%
                            </div>
                        </div>
                        <div>
                            <x-input-label value="IVA / Natura" />
                            <div class="mt-1 rounded-xl border border-line bg-white px-3.5 py-3 text-sm font-bold text-ink" data-invoice-vat-display>
                                {{ number_format((float) ($defaultInvoiceService['vat_rate'] ?? 0), 2, ',', '.') }}% {{ $defaultInvoiceService['vat_nature'] ?? $invoiceSettings['invoice_vat_nature'] }}
                            </div>
                        </div>
                        <div>
                            <x-input-label value="Bollo" />
                            <div class="mt-1 rounded-xl border border-line bg-white px-3.5 py-3 text-sm font-bold text-ink" data-invoice-stamp-display>
                                € 0,00
                            </div>
                        </div>
                        <div>
                            <x-input-label value="Totale fattura" />
                            <div class="mt-1 rounded-xl border-2 border-sage/30 bg-mist px-3.5 py-3 text-sm font-bold text-sage" data-invoice-total-display>
                                € {{ number_format((float) ($defaultInvoiceService['amount'] ?? 0), 2, ',', '.') }}
                            </div>
                        </div>
                    </div>
                    <textarea name="description" rows="2" class="app-field block w-full text-sm" placeholder="Descrizione" data-invoice-description>{{ old('description', $defaultInvoiceDescription) }}</textarea>
                    <x-primary-button>Registra fattura</x-primary-button>
                </form>

                <div class="mt-5 space-y-3">
                    @forelse ($invoicesByYear as $year => $invoices)
                        @php
                            $yearTotal = $invoices->sum(fn ($invoice) => \App\Support\InvoiceDefaults::amounts($invoice)['total']);
                            $openInvoiceId = (int) request('open_invoice');
                            $openYear = $openInvoiceId > 0 && $invoices->contains(fn ($invoice) => $invoice->id === $openInvoiceId);
                        @endphp
                        <details class="rounded-lg border-2 border-line bg-white p-4" {{ $openYear ? 'open' : '' }}>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                                <span class="inline-flex items-center gap-2 text-lg font-bold text-ink">
                                    <span class="text-sage">&#9656;</span>
                                    {{ $year }}
                                </span>
                                <span class="rounded-full bg-mist px-3 py-1 text-xs font-bold text-muted">
                                    {{ $invoices->count() }} fatture - € {{ number_format($yearTotal, 2, ',', '.') }}
                                </span>
                            </summary>
                            <div class="mt-4 space-y-3">
                                @foreach ($invoices as $invoice)
                                    @php
                                        $invoiceAmounts = \App\Support\InvoiceDefaults::amounts($invoice);
                                        $sent = filled($invoice->xml_downloaded_at);
                                    @endphp
                                    <div id="invoice-{{ $invoice->id }}" class="rounded-lg border border-line bg-mist/30 p-4">
                                        <div class="flex flex-wrap items-start justify-between gap-4">
                                            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                                <div class="flex items-start gap-2">
                                                    <span class="mt-0.5 text-sage">{!! $invoiceIcon('hash') !!}</span>
                                                    <div>
                                                        <p class="text-[11px] font-bold uppercase text-muted">Numero fattura</p>
                                                        <p class="text-sm font-bold text-ink">{{ $invoice->number ?: 'Fattura senza numero' }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-start gap-2">
                                                    <span class="mt-0.5 text-sage">{!! $invoiceIcon('calendar') !!}</span>
                                                    <div>
                                                        <p class="text-[11px] font-bold uppercase text-muted">Data emissione</p>
                                                        <p class="text-sm font-bold text-ink">{{ $invoice->issued_at?->format('d/m/Y') ?: 'Data non inserita' }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-start gap-2 sm:col-span-2 xl:col-span-1">
                                                    <span class="mt-0.5 text-sage">{!! $invoiceIcon('service') !!}</span>
                                                    <div>
                                                        <p class="text-[11px] font-bold uppercase text-muted">Servizio</p>
                                                        <p class="text-sm font-bold text-ink">{{ $invoice->service ?: 'Prestazione non indicata' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap justify-end gap-2 text-xs font-bold">
                                                <span class="inline-flex items-center gap-1 rounded-full border border-line bg-white px-2.5 py-1 text-muted">{!! $invoiceIcon('euro') !!} € {{ number_format($invoiceAmounts['total'], 2, ',', '.') }}</span>
                                                <span class="rounded-full border border-line bg-white px-2.5 py-1 text-muted">{{ $paymentLabel($invoice->payment_method) }}</span>
                                                <span class="rounded-full px-2.5 py-1 {{ $invoice->status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">{{ $invoiceStatusLabels[$invoice->status] ?? $invoice->status }}</span>
                                                <span class="rounded-full px-2.5 py-1 {{ $sent ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">{{ $sent ? 'Inviata' : 'Non inviata' }}</span>
                                            </div>
                                        </div>

                            <details class="mt-3" {{ (int) request('open_invoice') === $invoice->id ? 'open' : '' }}>
                                <summary class="cursor-pointer text-sm font-bold text-sage">Modifica fattura</summary>
                                <form method="POST" action="{{ route('patients.invoices.update', [$patient, $invoice]) }}" class="mt-4 space-y-3 rounded-lg border-2 border-line bg-white p-4">
                                    @csrf
                                    @method('PATCH')
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div>
                                            <x-input-label value="Numero fattura" />
                                            <x-text-input name="number" class="mt-1 block w-full" :value="$invoice->number" />
                                        </div>
                                        <div>
                                            <x-input-label value="Data emissione" />
                                            <x-text-input name="issued_at" type="date" class="mt-1 block w-full" :value="$invoice->issued_at->toDateString()" required />
                                        </div>
                                        <div>
                                            <x-input-label value="Descrizione servizio" />
                                            <x-text-input name="service" class="mt-1 block w-full" :value="$invoice->service" />
                                        </div>
                                        <div>
                                            <x-input-label value="Quantita" />
                                        <x-text-input name="quantity" type="number" step="1" min="1" class="mt-1 block w-full" :value="$invoice->quantity ?: 1" />
                                        <input type="hidden" name="line_amount" value="{{ $invoice->line_amount ?: $invoice->amount }}">
                                        </div>
                                        <div>
                                            <x-input-label value="Totale prestazione" />
                                            <x-text-input name="amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="$invoice->amount" required />
                                        </div>
                                        <div>
                                            <x-input-label value="Metodo di pagamento" />
                                            <select name="payment_method" class="app-field mt-1 block w-full">
                                                @foreach ($paymentMethods as $code => $label)
                                                    <option value="{{ $code }}" @selected($invoice->payment_method === $code)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <x-input-label value="Data pagamento" />
                                            <x-text-input name="payment_date" type="date" class="mt-1 block w-full" :value="($invoice->payment_date ?: $invoice->issued_at)?->toDateString()" />
                                        </div>
                                        <div>
                                            <x-input-label value="Stato fattura" />
                                            <select name="status" class="app-field mt-1 block w-full">
                                                @foreach ($invoiceStatusLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected($invoice->status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <input type="hidden" name="description" value="{{ $invoice->description }}">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <button form="delete-invoice-{{ $invoice->id }}" class="text-sm font-medium text-red-700 hover:text-red-900" onclick="return confirm('Eliminare questa fattura?')">Elimina fattura</button>
                                        <div class="flex flex-wrap gap-3">
                                            <a href="{{ route('patients.invoices.preview', [$patient, $invoice]) }}" class="inline-flex items-center justify-center rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist">Anteprima fattura<span class="sr-only"> Stampa</span></a>
                                            <x-primary-button>Salva fattura</x-primary-button>
                                        </div>
                                    </div>
                                </form>
                                <form id="delete-invoice-{{ $invoice->id }}" method="POST" action="{{ route('patients.invoices.destroy', [$patient, $invoice]) }}" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </details>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @empty
                        <p class="rounded-lg border-2 border-line bg-white p-4 text-sm text-muted">Nessuna fattura registrata.</p>
                    @endforelse
                </div>

            </section>
            @endif

            @if ($section === 'privacy')
            @php
                $privacyConsent = $patient->privacyConsent;
                $signedAtValue = old('signed_at', $privacyConsent?->signed_at?->toDateString() ?? now()->toDateString());
                $signatureValue = old('signature_data', $privacyConsent?->signature_data);
            @endphp
            <section id="privacy" class="app-card p-4 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">Privacy e consenso</h3>
                        <p class="mt-1 text-sm text-gray-500">Consulta il PDF del consenso e raccogli la firma digitale del paziente.</p>
                    </div>
                    @if ($privacyConsent?->signature_data)
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700">Consenso firmato</span>
                    @else
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-medium text-amber-700">Firma mancante</span>
                    @endif
                </div>

                <div class="mt-5 rounded-lg border-2 border-line bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Documento consenso privacy</p>
                            <h4 class="mt-1 text-xl font-bold text-ink">Consenso informato e informativa GDPR</h4>
                            <p class="mt-2 text-sm text-muted">Il PDF viene compilato con i dati anagrafici del paziente, tutti i consensi su Acconsento e la data odierna.</p>
                        </div>
                        <button type="button" id="open-privacy-pdf" class="rounded-xl bg-sage px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#4f7f75]">
                            Visualizza consenso privacy
                        </button>
                    </div>
                </div>

                <form id="privacy-signature-form" method="POST" action="{{ route('patients.privacy-consent.store', $patient) }}" class="hidden">
                    @csrf
                    <input type="hidden" name="signed_at" value="{{ $signedAtValue }}">
                    <input type="hidden" name="signature_method" value="digitale">
                    <input type="hidden" name="document_version" value="{{ old('document_version', $privacyConsent?->document_version ?? 'consenso-privacy-gdpr-v1') }}">
                    <input type="hidden" name="privacy_policy_accepted" value="1">
                    <input type="hidden" name="health_data_processing_accepted" value="1">
                    <input type="hidden" name="osteopathic_treatment_accepted" value="1">
                    <input type="hidden" name="doctor_data_sharing_accepted" value="1">
                    <input type="hidden" name="family_data_sharing_accepted" value="1">
                    <input type="hidden" name="whatsapp_reminders_accepted" value="1">
                    <input type="hidden" name="email_reminders_accepted" value="1">
                    <input type="hidden" name="sms_reminders_accepted" value="1">
                    <input type="hidden" name="marketing_accepted" value="0">
                    <input type="hidden" name="notes" value="{{ old('notes', $privacyConsent?->notes) }}">
                    <input type="hidden" id="privacy_signature_data" name="signature_data" value="{{ $signatureValue }}">
                </form>

                <div id="privacy-pdf-modal" class="fixed inset-0 z-50 hidden bg-slate-950/55 p-1 backdrop-blur-sm sm:p-2" aria-hidden="true">
                    <div class="mx-auto flex flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-2xl" style="height: calc(100vh - 16px); width: calc(100vw - 16px); max-width: none;">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[.12em] text-muted">Consenso privacy</p>
                                <h3 class="text-lg font-bold text-ink">PDF compilato</h3>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" id="open-privacy-signature" class="inline-flex items-center justify-center rounded-xl border border-sage bg-white px-4 py-2 text-sm font-bold text-sage hover:bg-mist">Firma consenso privacy</button>
                                <button type="button" id="close-privacy-pdf" class="inline-flex items-center justify-center rounded-xl bg-sage px-4 py-2 text-sm font-bold text-white hover:bg-[#4f7f75]">Chiudi</button>
                            </div>
                        </div>
                        <iframe id="privacy-pdf-frame" src="about:blank" class="block min-h-0 flex-1 border-0 bg-white" style="height: calc(100vh - 104px); width: 100%;" title="PDF consenso privacy"></iframe>
                    </div>
                </div>

                <div id="privacy-signature-modal" class="fixed inset-0 hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm" style="z-index: 99999;" aria-hidden="true">
                    <div class="w-full max-w-2xl rounded-2xl border border-line bg-white p-5 shadow-2xl">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[.12em] text-muted">Firma digitale</p>
                                <h3 class="text-lg font-bold text-ink">Firma consenso privacy</h3>
                                <p class="mt-1 text-sm text-muted">Firma con mouse, touch o tavoletta grafica esterna.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" id="clear-privacy-signature" class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-ink hover:bg-mist">Cancella firma</button>
                                <button type="submit" form="privacy-signature-form" class="inline-flex items-center justify-center rounded-xl bg-sage px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-[#4f7f75] focus:outline-none focus:ring-2 focus:ring-sage/20 focus:ring-offset-2">
                                    Applica firma
                                </button>
                                <button type="button" id="close-privacy-signature" class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-ink hover:bg-mist">Chiudi</button>
                            </div>
                        </div>
                        <div class="mt-4 overflow-hidden rounded-lg border-2 border-line bg-white">
                            <canvas id="privacy-signature-pad" class="block h-44 w-full touch-none bg-white"></canvas>
                        </div>
                    </div>
                </div>

                <script>
                    (() => {
                        const pdfModal = document.getElementById('privacy-pdf-modal');
                        const pdfFrame = document.getElementById('privacy-pdf-frame');
                        const openPdf = document.getElementById('open-privacy-pdf');
                        const closePdf = document.getElementById('close-privacy-pdf');
                        const signatureModal = document.getElementById('privacy-signature-modal');
                        const openSignature = document.getElementById('open-privacy-signature');
                        const closeSignature = document.getElementById('close-privacy-signature');
                        const canvas = document.getElementById('privacy-signature-pad');
                        const input = document.getElementById('privacy_signature_data');
                        const clearButton = document.getElementById('clear-privacy-signature');

                        if (! pdfModal || ! pdfFrame || ! openPdf || ! closePdf || ! signatureModal || ! openSignature || ! closeSignature || ! canvas || ! input || ! clearButton) {
                            return;
                        }

                        const openModal = (modal) => {
                            modal.classList.remove('hidden');
                            modal.setAttribute('aria-hidden', 'false');
                        };
                        const closeModal = (modal) => {
                            modal.classList.add('hidden');
                            modal.setAttribute('aria-hidden', 'true');
                        };

                        openPdf.addEventListener('click', () => {
                            pdfFrame.src = '{{ route('patients.privacy-consent.pdf', $patient) }}#toolbar=0&navpanes=0&zoom=page-width';
                            pdfFrame.style.visibility = 'visible';
                            pdfFrame.style.display = 'block';
                            openModal(pdfModal);
                        });
                        closePdf.addEventListener('click', () => {
                            closeModal(pdfModal);
                            pdfFrame.src = 'about:blank';
                        });
                        openSignature.addEventListener('click', () => {
                            pdfFrame.style.visibility = 'hidden';
                            pdfFrame.style.display = 'none';
                            openModal(signatureModal);
                            setTimeout(resize, 50);
                        });
                        closeSignature.addEventListener('click', () => {
                            closeModal(signatureModal);
                            pdfFrame.style.visibility = 'visible';
                            pdfFrame.style.display = 'block';
                        });

                        const context = canvas.getContext('2d');
                        let drawing = false;
                        let hasSignature = Boolean(input.value);

                        const resize = () => {
                            const ratio = window.devicePixelRatio || 1;
                            const rect = canvas.getBoundingClientRect();
                            canvas.width = Math.max(rect.width * ratio, 1);
                            canvas.height = Math.max(rect.height * ratio, 1);
                            context.setTransform(ratio, 0, 0, ratio, 0, 0);
                            context.lineCap = 'round';
                            context.lineJoin = 'round';
                            context.lineWidth = 2.4;
                            context.strokeStyle = '#102927';

                            if (input.value) {
                                const image = new Image();
                                image.onload = () => context.drawImage(image, 0, 0, rect.width, rect.height);
                                image.src = input.value;
                            }
                        };

                        const point = (event) => {
                            const rect = canvas.getBoundingClientRect();
                            const touch = event.touches?.[0] || event.changedTouches?.[0];
                            const source = touch || event;
                            return { x: source.clientX - rect.left, y: source.clientY - rect.top };
                        };
                        const start = (event) => {
                            event.preventDefault();
                            drawing = true;
                            hasSignature = true;
                            const p = point(event);
                            context.beginPath();
                            context.moveTo(p.x, p.y);
                        };
                        const move = (event) => {
                            if (! drawing) return;
                            event.preventDefault();
                            const p = point(event);
                            context.lineTo(p.x, p.y);
                            context.stroke();
                        };
                        const end = () => {
                            if (! drawing) return;
                            drawing = false;
                            if (hasSignature) input.value = canvas.toDataURL('image/png');
                        };

                        clearButton.addEventListener('click', () => {
                            context.clearRect(0, 0, canvas.width, canvas.height);
                            input.value = '';
                            hasSignature = false;
                        });
                        canvas.addEventListener('mousedown', start);
                        canvas.addEventListener('mousemove', move);
                        canvas.addEventListener('mouseup', end);
                        canvas.addEventListener('mouseleave', end);
                        canvas.addEventListener('touchstart', start, { passive: false });
                        canvas.addEventListener('touchmove', move, { passive: false });
                        canvas.addEventListener('touchend', end);
                        window.addEventListener('resize', resize);
                        resize();

                        @if (session('open_privacy_pdf'))
                            pdfFrame.src = '{{ route('patients.privacy-consent.pdf', $patient) }}#toolbar=0&navpanes=0&zoom=page-width';
                            pdfFrame.style.visibility = 'visible';
                            pdfFrame.style.display = 'block';
                            openModal(pdfModal);
                        @endif
                    })();
                </script>
            </section>
            @endif
        </div>
    </div>
</x-app-layout>

