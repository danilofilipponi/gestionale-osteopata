<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Fatture</h2>
                <p class="mt-1 text-sm text-gray-500">Riepilogo, ricerca e consultazione dello storico fatture.</p>
            </div>
            <a href="{{ route('settings.invoices') }}" class="rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist">
                Impostazioni fatture
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="app-section space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="app-card p-5">
                    <p class="text-sm text-gray-500">Fatture trovate</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $summary['count'] }}</p>
                </div>
                <div class="app-card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm text-gray-500">Totale fatturato</p>
                        <form method="GET">
                            @foreach (['search', 'status', 'from', 'to', 'summary_month'] as $key)
                                <input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">
                            @endforeach
                            <select name="summary_year" class="w-20 rounded-lg border-line bg-white py-1 pl-3 pr-8 text-xs font-bold text-muted shadow-sm" onchange="this.form.submit()">
                                @forelse ($availableYears as $year)
                                    <option value="{{ $year }}" @selected((int) $filters['summary_year'] === $year)>{{ $year }}</option>
                                @empty
                                    <option value="{{ $filters['summary_year'] }}">{{ $filters['summary_year'] }}</option>
                                @endforelse
                            </select>
                        </form>
                    </div>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">€ {{ number_format($summary['total'], 2, ',', '.') }}</p>
                </div>
                <div class="app-card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm text-gray-500">Incassato</p>
                        <form method="GET">
                            @foreach (['search', 'status', 'from', 'to', 'summary_year'] as $key)
                                <input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">
                            @endforeach
                            <select name="summary_month" class="w-28 rounded-lg border-line bg-white py-1 pl-3 pr-8 text-xs font-bold text-muted shadow-sm" onchange="this.form.submit()">
                                @php
                                    $months = $availableMonths->contains((int) $filters['summary_month'])
                                        ? $availableMonths
                                        : $availableMonths->push((int) $filters['summary_month'])->unique()->sort()->values();
                                @endphp
                                @forelse ($months as $month)
                                    <option value="{{ $month }}" @selected((int) $filters['summary_month'] === $month)>{{ $monthLabels[$month] }}</option>
                                @empty
                                    <option value="{{ $filters['summary_month'] }}">{{ $monthLabels[(int) $filters['summary_month']] }}</option>
                                @endforelse
                            </select>
                        </form>
                    </div>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">€ {{ number_format($summary['paid'], 2, ',', '.') }}</p>
                </div>
            </div>

            <section class="app-card p-6">
                <form method="GET" class="grid gap-4 lg:grid-cols-[1fr_180px_160px_160px_auto] lg:items-end">
                    <input type="hidden" name="summary_year" value="{{ $filters['summary_year'] }}">
                    <input type="hidden" name="summary_month" value="{{ $filters['summary_month'] }}">
                    <div>
                        <x-input-label for="search" value="Cerca" />
                        <input id="search" name="search" value="{{ $filters['search'] }}" class="app-field mt-1 block w-full" placeholder="Numero, paziente, codice fiscale o prestazione">
                    </div>
                    <div>
                        <x-input-label for="status" value="Stato" />
                        <select id="status" name="status" class="app-field mt-1 block w-full">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="from" value="Da" />
                        <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="$filters['from']" />
                    </div>
                    <div>
                        <x-input-label for="to" value="A" />
                        <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="$filters['to']" />
                    </div>
                    <div class="flex gap-3">
                        <button class="rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist">Filtra</button>
                        <a href="{{ route('invoices.index') }}" class="rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-muted shadow-sm hover:bg-mist hover:text-ink">Reset</a>
                    </div>
                </form>

                <div class="mt-5 flex flex-wrap gap-3">
                    @foreach ($statuses as $value => $label)
                        @php
                            $count = $value === 'all' ? $summary['count'] : (int) ($statusCounts[$value]->count ?? 0);
                            $statusUrl = route('invoices.index', array_filter([
                                'status' => $value,
                                'search' => $filters['search'],
                                'from' => $filters['from'],
                                'to' => $filters['to'],
                                'summary_year' => $filters['summary_year'],
                                'summary_month' => $filters['summary_month'],
                            ]));
                        @endphp
                        <a href="{{ $statusUrl }}" class="rounded-xl border border-line px-4 py-2 text-sm font-bold {{ $filters['status'] === $value ? 'bg-sage text-white' : 'bg-white text-muted hover:bg-mist hover:text-ink' }}">
                            {{ $label }} - {{ $count }}
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="app-card overflow-hidden">
                <div class="border-b border-line bg-white px-6 py-4">
                    <h3 class="font-semibold text-gray-900">Fatture di {{ $monthLabels[(int) $filters['summary_month']] }} {{ $filters['summary_year'] }}</h3>
                    <p class="mt-1 text-sm text-muted">L'elenco segue l'anno e il mese selezionati nei riquadri Totale fatturato e Incassato.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1080px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-line bg-mist text-xs uppercase text-muted">
                                <th class="px-6 py-4"></th>
                                <th class="px-6 py-4">Numero</th>
                                <th class="px-6 py-4">Data</th>
                                <th class="px-6 py-4">Paziente</th>
                                <th class="px-6 py-4">Prestazione</th>
                                <th class="px-6 py-4">Stato</th>
                                <th class="px-6 py-4 text-right">Importo</th>
                                <th class="px-6 py-4 text-center">Inviata</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line bg-white">
                            @forelse ($invoices as $invoice)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('patients.invoices.index', ['patient' => $invoice->patient, 'open_invoice' => $invoice->id]) }}#invoice-{{ $invoice->id }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-line bg-white text-sage shadow-sm hover:bg-mist" title="Apri dettaglio fattura">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M15 3h6v6" />
                                                <path d="M10 14 21 3" />
                                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                                            </svg>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('patients.invoices.index', $invoice->patient) }}" class="font-bold text-ink hover:text-sage">
                                            {{ $invoice->number ?: 'Senza numero' }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $invoice->issued_at?->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('patients.show', $invoice->patient) }}" class="font-semibold text-gray-900 hover:text-sage">{{ $invoice->patient->list_name }}</a>
                                        <p class="mt-1 text-xs text-muted">{{ $invoice->patient->fiscal_code ?: 'Codice fiscale non inserito' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $invoice->service ?: 'Prestazione non indicata' }}</td>
                                    <td class="px-6 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $invoice->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : ($invoice->status === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                                            {{ $statuses[$invoice->status] ?? $invoice->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-ink">€ {{ number_format($invoice->amount, 2, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($invoice->xml_downloaded_at)
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-lg font-black text-emerald-700" title="XML scaricato">✓</span>
                                        @else
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-red-50 text-lg font-black text-red-700" title="XML non scaricato">×</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">Nessuna fattura trovata.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div>
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
