<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Agenda</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }}</p>
            </div>
            <div class="flex gap-2">
                @foreach (['day' => 'Giorno', 'week' => 'Settimana', 'month' => 'Mese'] as $key => $label)
                    <a href="{{ route('appointments.index', ['view' => $key, 'date' => $date->toDateString()]) }}" class="rounded-xl px-3 py-2 text-sm font-bold {{ $view === $key ? 'bg-sage text-white' : 'border border-line bg-white text-muted hover:bg-mist hover:text-ink' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="app-section space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <section class="app-card p-6">
                <h3 class="font-semibold text-gray-900">Nuovo appuntamento</h3>
                <form method="POST" action="{{ route('appointments.store') }}" class="mt-4 grid gap-4 md:grid-cols-4">
                    @csrf
                    <select name="patient_id" class="app-field">
                        <option value="">Impegno personale</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->full_name }}</option>
                        @endforeach
                    </select>
                    <x-text-input name="title" placeholder="Titolo" required />
                    <x-text-input name="starts_at" type="datetime-local" required />
                    <x-text-input name="ends_at" type="datetime-local" required />
                    <select name="type" class="app-field">
                        <option value="visit">Visita</option>
                        <option value="personal">Personale</option>
                        <option value="holiday">Ferie</option>
                        <option value="absence">Assenza</option>
                    </select>
                    <select name="status" class="app-field">
                        <option value="scheduled">Programmato</option>
                        <option value="confirmed">Confermato</option>
                        <option value="completed">Svolto</option>
                        <option value="cancelled">Annullato</option>
                        <option value="no_show">Non presentato</option>
                    </select>
                    <x-text-input name="color" placeholder="Colore es. #2563eb" />
                    <x-text-input name="notes" placeholder="Note" />
                    <div class="md:col-span-4">
                        <x-primary-button>Crea appuntamento</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="app-card p-6">
                <h3 class="font-semibold text-gray-900">Appuntamenti</h3>
                <div class="mt-4 divide-y divide-gray-100">
                    @forelse ($appointments as $appointment)
                        <div class="py-4">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $appointment->title }}</p>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ $appointment->starts_at->format('d/m/Y H:i') }} - {{ $appointment->ends_at->format('H:i') }}
                                        @if ($appointment->patient)
                                            - {{ $appointment->patient->full_name }}
                                        @endif
                                    </p>
                                </div>
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">{{ $appointment->status }}</span>
                            </div>
                            <details class="mt-3">
                                <summary class="cursor-pointer text-sm font-medium text-gray-700">Modifica appuntamento</summary>
                                <form method="POST" action="{{ route('appointments.update', $appointment) }}" class="mt-4 grid gap-3 rounded-md border border-gray-200 p-4 md:grid-cols-4">
                                    @csrf
                                    @method('PATCH')
                                    <select name="patient_id" class="rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                        <option value="">Impegno personale</option>
                                        @foreach ($patients as $patient)
                                            <option value="{{ $patient->id }}" @selected($appointment->patient_id === $patient->id)>{{ $patient->full_name }}</option>
                                        @endforeach
                                    </select>
                                    <x-text-input name="title" :value="$appointment->title" required />
                                    <x-text-input name="starts_at" type="datetime-local" :value="$appointment->starts_at->format('Y-m-d\\TH:i')" required />
                                    <x-text-input name="ends_at" type="datetime-local" :value="$appointment->ends_at->format('Y-m-d\\TH:i')" required />
                                    <select name="type" class="rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                        @foreach (['visit' => 'Visita', 'personal' => 'Personale', 'holiday' => 'Ferie', 'absence' => 'Assenza'] as $value => $label)
                                            <option value="{{ $value }}" @selected($appointment->type === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                                        @foreach (['scheduled' => 'Programmato', 'confirmed' => 'Confermato', 'completed' => 'Svolto', 'cancelled' => 'Annullato', 'no_show' => 'Non presentato'] as $value => $label)
                                            <option value="{{ $value }}" @selected($appointment->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <x-text-input name="color" :value="$appointment->color" />
                                    <x-text-input name="notes" :value="$appointment->notes" />
                                    <div class="flex justify-between gap-3 md:col-span-4">
                                        <button form="delete-appointment-{{ $appointment->id }}" class="text-sm font-medium text-red-700 hover:text-red-900" onclick="return confirm('Eliminare questo appuntamento?')">Elimina</button>
                                        <x-primary-button>Salva appuntamento</x-primary-button>
                                    </div>
                                </form>
                                <form id="delete-appointment-{{ $appointment->id }}" method="POST" action="{{ route('appointments.destroy', $appointment) }}" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </details>
                        </div>
                    @empty
                        <p class="py-6 text-sm text-gray-500">Nessun appuntamento nel periodo selezionato.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
