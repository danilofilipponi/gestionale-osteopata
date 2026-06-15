@if ($pendingPatientMatches->isNotEmpty())
    <div id="patient-match-modal" class="fixed inset-0 hidden items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-6" style="z-index: 2147483647;" aria-hidden="true">
        <div class="relative flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border-2 border-line bg-white shadow-2xl">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-line bg-mist px-5 py-4">
                <div>
                    <p class="text-xs font-bold uppercase text-muted">Google Calendar</p>
                    <h3 class="mt-1 text-xl font-bold text-ink">Appuntamenti da abbinare ai pazienti</h3>
                    <p class="mt-1 text-sm font-semibold text-muted">Conferma il paziente corretto oppure crea una nuova scheda.</p>
                </div>
                <button type="button" data-close-patient-match-modal class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-ink shadow-sm hover:bg-mist">
                    Chiudi
                </button>
            </div>

            <div class="space-y-4 overflow-y-auto px-5 py-5">
                @foreach ($pendingPatientMatches as $match)
                    @php
                        $appointment = $match['appointment'];
                        $suggestions = $match['suggestions'];
                    @endphp
                    <div class="rounded-2xl border border-line bg-white p-4 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase text-muted">Evento Google</p>
                                <h4 class="mt-1 text-lg font-bold text-ink">{{ $appointment->title }}</h4>
                                <p class="mt-1 text-sm font-semibold text-muted">{{ $appointment->starts_at->format('d/m/Y H:i') }} - {{ $appointment->ends_at->format('H:i') }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('appointments.patient-match.ignore', $appointment) }}">
                                    @csrf
                                    <button class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-muted shadow-sm hover:bg-mist">
                                        Non abbinare
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('appointments.patient-match.new-patient', $appointment) }}">
                                    @csrf
                                    <button class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-sage shadow-sm hover:bg-mist">
                                        Nuovo paziente
                                    </button>
                                </form>
                            </div>
                        </div>

                        @if ($suggestions->isNotEmpty())
                            <div class="mt-4 grid gap-2 md:grid-cols-2">
                                @foreach ($suggestions as $suggestion)
                                    <form method="POST" action="{{ route('appointments.patient-match.resolve', $appointment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="patient_id" value="{{ $suggestion['patient']->id }}">
                                        <button class="flex w-full items-center justify-between gap-3 rounded-xl border border-line bg-mist/60 px-4 py-3 text-left text-sm hover:bg-mist">
                                            <span>
                                                <span class="block font-bold text-ink">{{ $suggestion['patient']->list_name }}</span>
                                                <span class="block text-xs text-muted">{{ collect([$suggestion['patient']->phone, $suggestion['patient']->email])->filter()->join(' - ') ?: 'Dati contatto non presenti' }}</span>
                                            </span>
                                            <span class="rounded-full bg-white px-2 py-1 text-xs font-bold text-sage">{{ $suggestion['score'] }}%</span>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-4 rounded-xl border border-dashed border-line bg-mist px-4 py-3 text-sm font-semibold text-muted">
                                Nessuna corrispondenza sicura trovata. Puoi creare una nuova scheda oppure usare "Non abbinare" per non riproporre piu questa ricerca.
                            </div>
                        @endif

                        <form method="POST" action="{{ route('appointments.patient-match.resolve', $appointment) }}" class="mt-4 grid gap-2 border-t border-line pt-4 md:grid-cols-[1fr_auto]">
                            @csrf
                            @method('PATCH')
                            <select name="patient_id" class="app-field" required>
                                <option value="">Scegli manualmente un paziente...</option>
                                @foreach ($patients as $patient)
                                    <option value="{{ $patient->id }}">{{ $patient->list_name }}</option>
                                @endforeach
                            </select>
                            <button class="rounded-xl bg-sage px-4 py-2 text-sm font-bold text-white hover:bg-[#4f7f75]">
                                Abbina
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
