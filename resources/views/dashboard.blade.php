<x-app-layout>
    <style>
        .dashboard-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (max-width: 900px) {
            .dashboard-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
                <p class="mt-1 text-sm text-gray-500">Riepilogo operativo dello studio per oggi e per il mese corrente.</p>
            </div>
            <a href="{{ route('patients.create') }}" class="inline-flex items-center rounded-xl bg-sage px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#4f7f75]">
                Nuovo paziente
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="app-section space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="dashboard-summary-grid">
                <section class="app-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-500">Appuntamenti odierni</p>
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-line bg-mist text-sage">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $todayAppointments->count() }}</p>
                    <p class="mt-1 text-xs font-semibold text-muted">{{ now()->translatedFormat('l d F') }}</p>
                </section>

                <section class="app-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-500">Incasso previsto giornata</p>
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-line bg-mist text-sage">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-semibold text-gray-900">EUR {{ number_format($expectedDailyIncome, 2, ',', '.') }}</p>
                    <p class="mt-1 text-xs font-semibold text-muted">Da appuntamenti fatturabili di oggi</p>
                </section>

                <section class="app-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-500">Fatturato mensile</p>
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-line bg-mist text-sage">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-semibold text-gray-900">EUR {{ number_format($monthlyRevenue, 2, ',', '.') }}</p>
                    <p class="mt-1 text-xs font-semibold text-muted">Emesso nel mese corrente</p>
                </section>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.25fr_.75fr]">
                <section class="app-card overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-white px-6 py-4">
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Agenda giornaliera</p>
                            <h3 class="mt-1 text-lg font-semibold text-gray-900">{{ now()->translatedFormat('l d F Y') }}</h3>
                        </div>
                        <a href="{{ route('appointments.index', ['view' => 'day', 'date' => now()->toDateString()]) }}" class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-ink shadow-sm hover:bg-mist">
                            Apri agenda
                        </a>
                    </div>
                    <div class="divide-y divide-line bg-white">
                        @forelse ($todayAppointments as $appointment)
                            @php
                                $appointmentColor = $appointment->color ?: '#5f948a';
                                $appointmentName = $appointment->patient?->list_name ?: $appointment->title;
                                $appointmentSubtitle = $appointment->patient?->list_name ? $appointment->title : 'Impegno personale';
                            @endphp
                            <a href="{{ route('appointments.index', ['view' => 'day', 'date' => $appointment->starts_at->toDateString()]) }}" class="grid grid-cols-[120px_1fr] hover:bg-gray-50">
                                <div class="flex items-center border-r border-line px-6 py-2.5">
                                    <span class="whitespace-nowrap text-sm font-bold text-ink">
                                    {{ $appointment->starts_at->format('H:i') }}
                                    <span class="text-muted">-</span>
                                    {{ $appointment->ends_at->format('H:i') }}
                                    </span>
                                </div>
                                <div class="min-w-0 px-5 py-2.5">
                                    <p class="flex items-center gap-2 truncate text-sm font-bold text-ink">
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $appointmentColor }}"></span>
                                        {{ $appointmentName }}
                                    </p>
                                    <p class="mt-0.5 truncate text-xs text-muted">{{ $appointmentSubtitle }}</p>
                                </div>
                            </a>
                        @empty
                            <p class="px-6 py-12 text-center text-sm text-gray-500">Nessun appuntamento in agenda oggi.</p>
                        @endforelse
                    </div>
                </section>

                <section class="app-card overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-white px-6 py-4">
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Pazienti</p>
                            <h3 class="mt-1 text-lg font-semibold text-gray-900">Ultimi 5 registrati</h3>
                        </div>
                        <a href="{{ route('patients.index') }}" class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-ink shadow-sm hover:bg-mist">
                            Vedi tutti
                        </a>
                    </div>
                    <div class="divide-y divide-line bg-white">
                        @forelse ($recentPatients as $patient)
                            <a href="{{ route('patients.show', $patient) }}" class="flex items-center justify-between gap-4 px-6 py-2.5 hover:bg-gray-50">
                                <span class="min-w-0">
                                    <span class="block truncate font-bold text-ink">{{ $patient->list_name }}</span>
                                    <span class="mt-0.5 block truncate text-xs text-muted">{{ $patient->phone ?: $patient->email ?: 'Contatto non inserito' }}</span>
                                </span>
                                <span class="shrink-0 text-xs font-semibold text-muted">{{ $patient->created_at->format('d/m/Y') }}</span>
                            </a>
                        @empty
                            <p class="px-6 py-12 text-center text-sm text-gray-500">Nessun paziente ancora registrato.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="app-card overflow-hidden">
                <div class="grid grid-cols-[150px_minmax(0,1fr)] gap-0">
                    <aside class="border-r border-line bg-white p-4">
                        <div class="inline-flex overflow-hidden rounded-t-md border-b border-sage text-xs font-bold">
                            <span class="bg-white px-3 py-2 text-ink">Mese</span>
                            <span class="bg-gray-200 px-3 py-2 text-muted">Trimestre</span>
                        </div>

                        <div class="mt-10">
                            <p class="text-base font-semibold leading-tight text-ink">Fatturato<br>attivo</p>
                            <p class="mt-3 text-xl font-black text-[#0070c9]">EUR {{ number_format($activeRevenueYearTotal, 2, ',', '.') }}</p>
                        </div>
                    </aside>

                    <div class="p-4">
                        <div class="relative flex items-center justify-center">
                            <h3 class="text-xl font-black text-ink">Fatturato {{ now()->year }}</h3>
                            <span class="ml-2 inline-flex h-6 w-6 items-center justify-center rounded-full border-2 border-[#0070c9] text-sm font-black text-[#0070c9]">i</span>
                        </div>

                        <div class="mt-5 grid grid-cols-[44px_1fr] gap-3">
                            <div class="relative h-64 text-[11px] font-semibold text-muted">
                                @foreach ([100, 75, 50, 25, 0] as $tick)
                                    @php
                                        $value = ($maxActiveRevenue / 100) * $tick;
                                    @endphp
                                    <span class="absolute right-0 -translate-y-1/2" style="top: {{ 100 - $tick }}%">{{ number_format($value, 0, ',', '.') }}</span>
                                @endforeach
                            </div>

                            <div class="relative h-64 overflow-hidden border-b border-l border-gray-400">
                                @foreach ([0, 25, 50, 75, 100] as $line)
                                    <div class="absolute left-0 right-0 border-t border-gray-300" style="bottom: {{ $line }}%"></div>
                                @endforeach

                                <div class="absolute inset-x-0 bottom-0 z-10 flex h-64 items-end gap-4 px-1">
                                    @foreach ($activeRevenueChart as $item)
                                        @php
                                            $barHeight = $item['total'] > 0 ? max(8, round(($item['total'] / $maxActiveRevenue) * 236)) : 0;
                                        @endphp
                                        <div class="flex h-full min-w-0 flex-1 flex-col items-center justify-end">
                                            <div class="w-full max-w-10 border border-[#0070c9] bg-[#dceeff] hover:bg-[#b7d7ee]" style="height: {{ $barHeight }}px;" title="{{ $item['month'] }}: EUR {{ number_format($item['total'], 2, ',', '.') }}"></div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div></div>
                            <div class="flex gap-4 px-1 pt-2">
                                @foreach ($activeRevenueChart as $item)
                                    <span class="min-w-0 flex-1 text-center text-[11px] font-semibold text-ink">{{ $item['month'] }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-center gap-2 text-xs font-semibold text-ink">
                            <span class="inline-block h-3 w-3 border border-[#0070c9] bg-[#dceeff]"></span>
                            Attivo
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
