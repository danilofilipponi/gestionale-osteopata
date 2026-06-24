@php
    $patient = $patient ?? null;
    $submitLabel = $submitLabel ?? 'Salva paziente';
    $cancelUrl = $cancelUrl ?? route('patients.index');
    $genderValue = old('gender', $patient?->gender);
    $countryValue = old('country_id', $patient?->country_id ?? 'IT');
    $customerTypeValue = old('customer_type', $patient?->customer_type ?? 'Privato');
    $countryOptions = [
        'IT' => 'IT - Italiana',
        'FR' => 'FR - Francia',
        'DE' => 'DE - Germania',
        'ES' => 'ES - Spagna',
        'CH' => 'CH - Svizzera',
        'AT' => 'AT - Austria',
        'BE' => 'BE - Belgio',
        'NL' => 'NL - Paesi Bassi',
        'GB' => 'GB - Regno Unito',
        'US' => 'US - Stati Uniti',
    ];
@endphp

<div class="grid gap-6 xl:grid-cols-[1fr_320px]">
    <form id="patient-form" method="POST" action="{{ $action }}" class="app-card p-6" data-patient-form data-unsaved-form>
        @csrf
        @if (! empty($pendingAppointmentId))
            <input type="hidden" name="appointment_id" value="{{ $pendingAppointmentId }}">
        @endif
        @isset($method)
            @method($method)
        @endisset

        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
            <div>
                <h3 class="text-lg font-bold text-ink">Dati anagrafici</h3>
                <p class="mt-1 text-sm text-muted">Inserisci i dati principali del paziente e completa i dettagli amministrativi.</p>
            </div>
            <span class="rounded-full bg-mist px-3 py-1 text-xs font-bold uppercase text-sage">Scheda paziente</span>
        </div>

        <div class="mt-6 space-y-8">
            <section>
                <h4 class="text-sm font-bold uppercase text-muted">Dati principali</h4>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="last_name" value="Cognome *" />
                        <x-text-input id="last_name" name="last_name" class="patient-preview-field mt-1 block w-full" data-preview-target="preview-last-name" :value="old('last_name', $patient?->last_name)" required autofocus />
                        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="first_name" value="Nome *" />
                        <x-text-input id="first_name" name="first_name" class="patient-preview-field mt-1 block w-full" data-preview-target="preview-first-name" :value="old('first_name', $patient?->first_name)" required />
                        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Telefono" />
                        <x-text-input id="phone" name="phone" class="patient-preview-field mt-1 block w-full" data-preview-target="preview-phone" :value="old('phone', $patient?->phone)" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Mail" />
                        <x-text-input id="email" name="email" type="email" class="patient-preview-field mt-1 block w-full" data-preview-target="preview-email" :value="old('email', $patient?->email)" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="pec" value="PEC" />
                        <x-text-input id="pec" name="pec" type="email" class="mt-1 block w-full" :value="old('pec', $patient?->pec)" />
                        <x-input-error :messages="$errors->get('pec')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="profession" value="Professione" />
                        <x-text-input id="profession" name="profession" class="patient-preview-field mt-1 block w-full" data-preview-target="preview-profession" :value="old('profession', $patient?->profession)" />
                    </div>
                    <div>
                        <x-input-label for="country_id" value="ID paese" />
                        <select id="country_id" name="country_id" class="app-field mt-1 block w-full">
                            @foreach ($countryOptions as $value => $label)
                                <option value="{{ $value }}" @selected($countryValue === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <section>
                <h4 class="text-sm font-bold uppercase text-muted">Nascita</h4>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="birth_date" value="Data di nascita" />
                        <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1 block w-full" :value="old('birth_date', $patient?->birth_date?->toDateString())" />
                        <p class="mt-2 text-xs text-muted">Eta: <span id="computed-age">n.d.</span></p>
                    </div>
                    <div>
                        <x-input-label for="gender" value="Sesso" />
                        <select id="gender" name="gender" class="app-field mt-1 block w-full">
                            <option value="">Non indicato</option>
                            @foreach (['F', 'M', 'Altro'] as $gender)
                                <option value="{{ $gender }}" @selected($genderValue === $gender)>{{ $gender }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="birth_place" value="Luogo di nascita" />
                        <x-text-input id="birth_place" name="birth_place" list="italian-cities" class="mt-1 block w-full" :value="old('birth_place', $patient?->birth_place)" placeholder="Cerca comune..." />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="fiscal_code" value="Codice fiscale" />
                        <div class="mt-1 flex gap-2">
                            <x-text-input id="fiscal_code" name="fiscal_code" class="patient-preview-field block w-full font-mono uppercase" data-preview-target="preview-fiscal-code" :value="old('fiscal_code', $patient?->fiscal_code)" maxlength="16" placeholder="Calcolato automaticamente" />
                            <button type="button" id="recalculate-fiscal-code" class="rounded-xl border border-line bg-white px-3 text-xs font-bold text-sage hover:bg-mist">Calcola</button>
                            <button type="button" id="normalize-fiscal-code" class="rounded-xl border border-line bg-white px-3 text-xs font-bold text-sage hover:bg-mist">ABC</button>
                        </div>
                        <p class="mt-2 text-xs text-muted">Si calcola automaticamente quando cognome, nome, data di nascita, sesso e luogo di nascita sono compilati.</p>
                    </div>
                </div>
            </section>

            <section>
                <h4 class="text-sm font-bold uppercase text-muted">Residenza</h4>
                <div class="mt-4 grid gap-4 md:grid-cols-4">
                    <div class="md:col-span-3">
                        <x-input-label for="address" value="Indirizzo" />
                        <x-text-input id="address" name="address" class="mt-1 block w-full" :value="old('address', $patient?->address)" />
                    </div>
                    <div>
                        <x-input-label for="street_number" value="Civico" />
                        <x-text-input id="street_number" name="street_number" class="mt-1 block w-full" :value="old('street_number', $patient?->street_number)" maxlength="20" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="city" value="Citta" />
                        <x-text-input id="city" name="city" list="italian-cities" class="mt-1 block w-full" :value="old('city', $patient?->city)" placeholder="Cerca comune..." />
                    </div>
                    <div>
                        <x-input-label for="province" value="Provincia" />
                        <x-text-input id="province" name="province" class="mt-1 block w-full uppercase" :value="old('province', $patient?->province)" maxlength="2" />
                        <p class="mt-2 text-xs text-muted">Calcolata automaticamente dalla citta.</p>
                    </div>
                    <div>
                        <x-input-label for="postal_code" value="CAP" />
                        <x-text-input id="postal_code" name="postal_code" class="mt-1 block w-full" :value="old('postal_code', $patient?->postal_code)" />
                        <p class="mt-2 text-xs text-muted">Calcolato automaticamente dalla citta.</p>
                    </div>
                </div>
            </section>

            <section>
                <h4 class="text-sm font-bold uppercase text-muted">Note</h4>
                <textarea id="notes" name="notes" rows="4" class="app-field mt-4 block w-full">{{ old('notes', $patient?->notes) }}</textarea>
            </section>

            <section>
                <h4 class="text-sm font-bold uppercase text-muted">Dati fiscali ed esportazione</h4>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="customer_type" value="Tipo cliente" />
                        <select id="customer_type" name="customer_type" class="app-field mt-1 block w-full">
                            @foreach (['Privato', 'Pubblica amministrazione'] as $type)
                                <option value="{{ $type }}" @selected($customerTypeValue === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="telematic_address" value="Codice SDI o PEC" />
                        <x-text-input id="telematic_address" name="telematic_address" class="mt-1 block w-full" :value="old('telematic_address', $patient?->telematic_address ?? '0000000')" />
                    </div>
                    <div>
                        <x-input-label for="vat_number" value="Partita IVA" />
                        <x-text-input id="vat_number" name="vat_number" class="mt-1 block w-full" :value="old('vat_number', $patient?->vat_number)" />
                    </div>
                    <div>
                        <x-input-label for="business_name" value="Denominazione" />
                        <x-text-input id="business_name" name="business_name" class="mt-1 block w-full" :value="old('business_name', $patient?->business_name)" />
                    </div>
                    <div>
                        <x-input-label for="eori_code" value="Codice EORI" />
                        <x-text-input id="eori_code" name="eori_code" class="mt-1 block w-full" :value="old('eori_code', $patient?->eori_code)" />
                    </div>
                </div>
            </section>
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-5">
            @isset($deleteFormId)
                <button form="{{ $deleteFormId }}" class="rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-bold text-red-700 hover:bg-red-50" onclick="return confirm('Eliminare questo paziente e tutti i dati collegati?')">Elimina paziente</button>
            @else
                <span></span>
            @endisset
            <div class="flex flex-wrap gap-3">
                <a href="{{ $cancelUrl }}" class="rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink hover:bg-mist">Annulla</a>
                <x-primary-button>{{ $submitLabel }}</x-primary-button>
            </div>
        </div>
    </form>

    <aside class="space-y-4">
        <div class="app-card p-6">
            <div class="flex items-center gap-4">
                <div class="grid h-14 w-14 place-items-center rounded-full bg-[#dbeae7] text-base font-bold text-sage">
                    <span id="preview-initials">--</span>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-lg font-bold text-ink"><span id="preview-last-name">{{ old('last_name', $patient?->last_name) ?: 'Cognome' }}</span> <span id="preview-first-name">{{ old('first_name', $patient?->first_name) ?: 'Nome' }}</span></p>
                    <p id="preview-profession" class="mt-1 truncate text-sm text-muted">{{ old('profession', $patient?->profession) ?: 'Professione non inserita' }}</p>
                </div>
            </div>
            <div class="mt-6 space-y-3 text-sm">
                <div class="border-b border-line pb-3">
                    <p class="text-xs font-bold uppercase text-muted">Telefono</p>
                    <p id="preview-phone" class="mt-1 font-bold text-ink">{{ old('phone', $patient?->phone) ?: 'Non inserito' }}</p>
                </div>
                <div class="border-b border-line pb-3">
                    <p class="text-xs font-bold uppercase text-muted">Email</p>
                    <p id="preview-email" class="mt-1 break-words font-bold text-ink">{{ old('email', $patient?->email) ?: 'Non inserita' }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase text-muted">Codice fiscale</p>
                    <p id="preview-fiscal-code" class="mt-1 break-words font-mono text-xs font-bold text-ink">{{ old('fiscal_code', $patient?->fiscal_code) ?: 'Non inserito' }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-line bg-mist p-5 text-sm text-muted">
            <p class="font-bold text-ink">Consiglio operativo</p>
            <p class="mt-2">Dopo il salvataggio troverai anamnesi, sedute, fatture e privacy nella cartella del paziente.</p>
        </div>
    </aside>
</div>

<datalist id="italian-cities"></datalist>

<div class="fixed inset-x-4 bottom-4 z-50 hidden rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 shadow-xl md:left-auto md:w-[440px]" data-unsaved-warning="patient-form">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="font-bold">Modifiche non salvate</p>
            <p class="mt-1 text-amber-900/80">Salva il paziente prima di uscire dalla pagina.</p>
        </div>
        <button type="submit" form="patient-form" class="rounded-xl bg-sage px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-[#4f7f75]">Salva paziente</button>
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const form = document.querySelector('[data-unsaved-form][id="patient-form"]');
            const warning = document.querySelector('[data-unsaved-warning="patient-form"]');

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
