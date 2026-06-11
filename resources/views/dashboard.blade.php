<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
            <a href="{{ route('patients.create') }}" class="inline-flex items-center rounded-xl bg-sage px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#4f7f75]">
                Nuovo paziente
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="app-section">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-5">
                <div class="app-card p-5">
                    <p class="text-sm text-gray-500">Pazienti</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $patientsCount }}</p>
                </div>
                <div class="app-card p-5">
                    <p class="text-sm text-gray-500">Sedute questo mese</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $sessionsThisMonth }}</p>
                </div>
                <div class="app-card p-5">
                    <p class="text-sm text-gray-500">Nuovi pazienti</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $newPatientsThisMonth }}</p>
                </div>
                <div class="app-card p-5">
                    <p class="text-sm text-gray-500">Incassi mese</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">EUR {{ number_format($paidInvoicesThisMonth, 2, ',', '.') }}</p>
                </div>
                <div class="app-card p-5">
                    <p class="text-sm text-gray-500">Fatture aperte</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">EUR {{ number_format($openInvoicesTotal, 2, ',', '.') }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <section class="app-card p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">Appuntamenti di oggi</h3>
                        <a href="{{ route('appointments.index', ['view' => 'day']) }}" class="text-sm font-medium text-gray-700 hover:text-gray-950">Apri agenda</a>
                    </div>
                    <div class="mt-4 divide-y divide-gray-100">
                        @forelse ($todayAppointments as $appointment)
                            <div class="py-3">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="font-medium text-gray-900">{{ $appointment->title }}</span>
                                    <span class="text-sm text-gray-500">{{ $appointment->starts_at->format('H:i') }}</span>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">{{ $appointment->patient?->list_name ?: 'Impegno personale' }}</p>
                            </div>
                        @empty
                            <p class="py-6 text-sm text-gray-500">Nessun appuntamento oggi.</p>
                        @endforelse
                    </div>
                </section>

                <section class="app-card p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">Pazienti recenti</h3>
                        <a href="{{ route('patients.index') }}" class="text-sm font-medium text-gray-700 hover:text-gray-950">Vedi tutti</a>
                    </div>
                    <div class="mt-4 divide-y divide-gray-100">
                        @forelse ($recentPatients as $patient)
                            <a href="{{ route('patients.show', $patient) }}" class="flex items-center justify-between py-3">
                                <span class="font-medium text-gray-900">{{ $patient->list_name }}</span>
                                <span class="text-sm text-gray-500">{{ $patient->phone ?: 'Telefono non inserito' }}</span>
                            </a>
                        @empty
                            <p class="py-6 text-sm text-gray-500">Nessun paziente ancora registrato.</p>
                        @endforelse
                    </div>
                </section>

                <section class="app-card p-6">
                    <h3 class="font-semibold text-gray-900">Fatture da emettere/incassare</h3>
                    <div class="mt-4 divide-y divide-gray-100">
                        @forelse ($openInvoices as $invoice)
                            <a href="{{ route('patients.invoices.index', $invoice->patient) }}" class="block py-3">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="font-medium text-gray-900">{{ $invoice->patient->list_name }}</span>
                                    <span class="text-sm text-gray-500">EUR {{ number_format($invoice->amount, 2, ',', '.') }}</span>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">{{ $invoice->number ?: 'Senza numero' }} - {{ $invoice->status }}</p>
                            </a>
                        @empty
                            <p class="py-6 text-sm text-gray-500">Nessuna fattura aperta.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
