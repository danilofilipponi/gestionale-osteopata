<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $patient->full_name }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $patient->phone ?: 'Telefono non inserito' }} · {{ $patient->email ?: 'Email non inserita' }}</p>
            </div>
            <a href="{{ route('patients.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Torna ai pazienti</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <section class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-gray-900">Cartella clinica</h3>
                <form method="POST" action="{{ route('patients.medical-record.store', $patient) }}" class="mt-4 grid gap-4 md:grid-cols-2">
                    @csrf
                    @foreach ([
                        'reason_for_visit' => 'Motivo della visita',
                        'anamnesis' => 'Anamnesi',
                        'diagnostic_notes' => 'Valutazione',
                        'treatment_plan' => 'Piano di trattamento',
                        'contraindications' => 'Controindicazioni'
                    ] as $field => $label)
                        <div class="{{ $field === 'contraindications' ? 'md:col-span-2' : '' }}">
                            <x-input-label :for="$field" :value="$label" />
                            <textarea id="{{ $field }}" name="{{ $field }}" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">{{ old($field, $patient->medicalRecord?->{$field}) }}</textarea>
                        </div>
                    @endforeach
                    <div class="md:col-span-2">
                        <x-primary-button>Salva cartella</x-primary-button>
                    </div>
                </form>
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-gray-900">Nuova seduta</h3>
                    <form method="POST" action="{{ route('patients.sessions.store', $patient) }}" class="mt-4 space-y-4">
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
                        <textarea name="outcome" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="Esito e indicazioni">{{ old('outcome') }}</textarea>
                        <div class="flex items-center gap-4">
                            <x-text-input name="fee" type="number" step="0.01" min="0" class="w-40" placeholder="Importo" :value="old('fee')" />
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="paid" value="1" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                                Pagata
                            </label>
                        </div>
                        <x-primary-button>Registra seduta</x-primary-button>
                    </form>
                </section>

                <section class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-gray-900">Nuova fattura</h3>
                    <form method="POST" action="{{ route('patients.invoices.store', $patient) }}" class="mt-4 space-y-4">
                        @csrf
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-text-input name="number" placeholder="Numero fattura" :value="old('number')" />
                            <x-text-input name="issued_at" type="date" :value="old('issued_at', now()->toDateString())" required />
                            <x-text-input name="amount" type="number" step="0.01" min="0" placeholder="Importo" :value="old('amount')" required />
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
                </section>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-gray-900">Sedute</h3>
                    <div class="mt-4 divide-y divide-gray-100">
                        @forelse ($patient->treatmentSessions as $session)
                            <div class="py-4">
                                <div class="flex items-center justify-between gap-4">
                                    <p class="font-medium text-gray-900">{{ $session->title }}</p>
                                    <p class="text-sm text-gray-500">{{ $session->session_date->format('d/m/Y') }}</p>
                                </div>
                                <p class="mt-2 text-sm text-gray-600">{{ $session->treatment ?: 'Nessun dettaglio trattamento inserito.' }}</p>
                                <p class="mt-2 text-sm text-gray-500">€ {{ number_format($session->fee ?? 0, 2, ',', '.') }} · {{ $session->paid ? 'Pagata' : 'Da saldare' }}</p>
                            </div>
                        @empty
                            <p class="py-6 text-sm text-gray-500">Nessuna seduta registrata.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-gray-900">Fatture</h3>
                    <div class="mt-4 divide-y divide-gray-100">
                        @forelse ($patient->invoices as $invoice)
                            <div class="flex items-center justify-between gap-4 py-4">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $invoice->number ?: 'Fattura senza numero' }}</p>
                                    <p class="text-sm text-gray-500">{{ $invoice->issued_at->format('d/m/Y') }} · {{ $invoice->status }}</p>
                                </div>
                                <p class="font-semibold text-gray-900">€ {{ number_format($invoice->amount, 2, ',', '.') }}</p>
                            </div>
                        @empty
                            <p class="py-6 text-sm text-gray-500">Nessuna fattura registrata.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
