<x-app-layout>
    <style>
        @media (min-width: 1180px) {
            .invoice-filter-form {
                display: grid;
                grid-template-columns: minmax(420px, 1fr) 180px 180px auto;
                align-items: end;
            }

            .invoice-filter-actions {
                justify-content: flex-end;
                white-space: nowrap;
            }
        }

        .invoice-export-modal[aria-hidden="true"] {
            display: none;
        }

        .invoice-export-modal[aria-hidden="false"] {
            display: flex;
        }

        .invoice-export-modal {
            align-items: center;
            background: rgba(15, 23, 42, 0.58);
            inset: 0;
            justify-content: center;
            padding: 18px;
            position: fixed;
            z-index: 2147483647;
        }

        .invoice-export-dialog {
            background: #ffffff;
            border: 2px solid #c4d9d4;
            border-radius: 18px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.32);
            max-height: calc(100vh - 36px);
            max-width: 560px;
            overflow: hidden;
            width: min(560px, calc(100vw - 36px));
        }

        .invoice-export-dialog p {
            overflow-wrap: anywhere;
        }

        .invoice-export-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        @media (max-width: 640px) {
            .invoice-export-actions {
                flex-direction: column-reverse;
            }
        }
    </style>

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
            <div class="grid grid-cols-3 gap-2 md:gap-4">
                <div class="app-card p-3 sm:p-5">
                    <p class="text-[11px] leading-tight text-gray-500 sm:text-sm">Fatture trovate</p>
                    <p class="mt-2 text-xl font-semibold text-gray-900 sm:text-3xl">{{ $summary['count'] }}</p>
                </div>
                <div class="app-card p-3 sm:p-5">
                    <div class="flex flex-col items-start gap-2 sm:flex-row sm:justify-between sm:gap-3">
                        <p class="text-[11px] leading-tight text-gray-500 sm:text-sm">Totale fatturato</p>
                        <form method="GET">
                            @foreach (['search', 'quick_filter', 'from', 'to', 'summary_month'] as $key)
                                <input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">
                            @endforeach
                            <select name="summary_year" class="w-[72px] rounded-lg border-line bg-white py-1 pl-2 pr-7 text-xs font-bold text-muted shadow-sm sm:w-20 sm:pl-3 sm:pr-8" onchange="this.form.submit()">
                                @forelse ($availableYears as $year)
                                    <option value="{{ $year }}" @selected((int) $filters['summary_year'] === $year)>{{ $year }}</option>
                                @empty
                                    <option value="{{ $filters['summary_year'] }}">{{ $filters['summary_year'] }}</option>
                                @endforelse
                            </select>
                        </form>
                    </div>
                    <p class="mt-2 whitespace-nowrap text-base font-semibold text-gray-900 sm:text-3xl">€ {{ number_format($summary['total'], 2, ',', '.') }}</p>
                </div>
                <div class="app-card p-3 sm:p-5">
                    <div class="flex flex-col items-start gap-2 sm:flex-row sm:justify-between sm:gap-3">
                        <p class="text-[11px] leading-tight text-gray-500 sm:text-sm">Incassato</p>
                        <form method="GET">
                            @foreach (['search', 'quick_filter', 'from', 'to', 'summary_year'] as $key)
                                <input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">
                            @endforeach
                            <select name="summary_month" class="w-[86px] rounded-lg border-line bg-white py-1 pl-2 pr-7 text-xs font-bold text-muted shadow-sm sm:w-28 sm:pl-3 sm:pr-8" onchange="this.form.submit()">
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
                    <p class="mt-2 whitespace-nowrap text-base font-semibold text-gray-900 sm:text-3xl">€ {{ number_format($summary['paid'], 2, ',', '.') }}</p>
                </div>
            </div>

            <section class="app-card p-4 sm:p-6">
                <form method="GET" class="invoice-filter-form grid gap-4">
                    <input type="hidden" name="summary_year" value="{{ $filters['summary_year'] }}">
                    <input type="hidden" name="summary_month" value="{{ $filters['summary_month'] }}">
                    <input type="hidden" name="quick_filter" value="{{ $filters['quick_filter'] }}">
                    <div>
                        <x-input-label for="search" value="Cerca" />
                        <input id="search" name="search" value="{{ $filters['search'] }}" class="app-field mt-1 block w-full" placeholder="Numero, paziente, codice fiscale o prestazione">
                    </div>
                    <div>
                        <x-input-label for="from" value="Da" />
                        <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="$filters['from']" />
                    </div>
                    <div>
                        <x-input-label for="to" value="A" />
                        <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="$filters['to']" />
                    </div>
                    <div class="invoice-filter-actions flex gap-3">
                        <button class="flex-1 rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist lg:flex-none">Filtra</button>
                        <a href="{{ route('invoices.index') }}" class="flex-1 rounded-xl border border-line bg-white px-4 py-2.5 text-center text-sm font-bold text-muted shadow-sm hover:bg-mist hover:text-ink lg:flex-none">Reset</a>
                    </div>
                </form>

                <div class="-mx-1 mt-5 flex flex-col gap-3 px-1 pb-1 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex gap-2 overflow-x-auto pb-1 sm:flex-wrap sm:gap-3 sm:overflow-visible sm:pb-0">
                        @foreach ($quickFilters as $value => $label)
                            @php
                                $filterUrl = route('invoices.index', array_filter([
                                    'quick_filter' => $value,
                                    'search' => $filters['search'],
                                    'from' => $filters['from'],
                                    'to' => $filters['to'],
                                    'summary_year' => $filters['summary_year'],
                                    'summary_month' => $filters['summary_month'],
                                ], fn ($item) => filled($item) || is_numeric($item)));
                            @endphp
                            <a href="{{ $filterUrl }}" class="shrink-0 rounded-xl border border-line px-4 py-2 text-sm font-bold {{ $filters['quick_filter'] === $value ? 'bg-sage text-white' : 'bg-white text-muted hover:bg-mist hover:text-ink' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                        @php
                            $clearQuickUrl = route('invoices.index', array_filter([
                                'search' => $filters['search'],
                                'from' => $filters['from'],
                                'to' => $filters['to'],
                                'summary_year' => $filters['summary_year'],
                                'summary_month' => $filters['summary_month'],
                            ], fn ($item) => filled($item) || is_numeric($item)));
                        @endphp
                        @if ($filters['quick_filter'])
                            <a href="{{ $clearQuickUrl }}" class="shrink-0 rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-muted hover:bg-mist hover:text-ink">
                                Tutte
                            </a>
                        @endif
                    </div>

                    @php
                        $exportParams = array_filter([
                            'quick_filter' => $filters['quick_filter'],
                            'search' => $filters['search'],
                            'from' => $filters['from'],
                            'to' => $filters['to'],
                            'summary_year' => $filters['summary_year'],
                            'summary_month' => $filters['summary_month'],
                        ], fn ($item) => filled($item) || is_numeric($item));
                        $exportFrom = $exportRange['from'] ?: 'non disponibile';
                        $exportTo = $exportRange['to'] ?: 'non disponibile';
                    @endphp
                    <button
                        type="button"
                        class="inline-flex shrink-0 items-center justify-center rounded-xl bg-sage px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#4f7f75] {{ $summary['count'] === 0 ? 'pointer-events-none opacity-50' : '' }}"
                        data-open-invoice-export-modal
                    >
                        Esporta selezione
                    </button>
                </div>
            </section>

            <section class="app-card overflow-hidden">
                <div class="border-b border-line bg-white px-6 py-4">
                    <h3 class="font-semibold text-gray-900">Fatture di {{ $monthLabels[(int) $filters['summary_month']] }} {{ $filters['summary_year'] }}</h3>
                    <p class="mt-1 text-sm text-muted">L'elenco segue l'anno e il mese selezionati nei riquadri Totale fatturato e Incassato.</p>
                </div>
                <div class="divide-y divide-line bg-white md:hidden">
                    @forelse ($invoices as $invoice)
                        <a href="{{ route('patients.invoices.index', ['patient' => $invoice->patient, 'open_invoice' => $invoice->id]) }}#invoice-{{ $invoice->id }}" class="block p-4 transition hover:bg-mist">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-ink">{{ $invoice->number ?: 'Senza numero' }}</span>
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $invoice->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : ($invoice->status === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                                            {{ $statuses[$invoice->status] ?? $invoice->status }}
                                        </span>
                                    </div>
                                    <p class="mt-1 truncate font-semibold text-gray-900">{{ $invoice->patient->list_name }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="font-bold text-ink">€ {{ number_format($invoice->amount, 2, ',', '.') }}</p>
                                    <p class="mt-1 text-xs text-muted">{{ $invoice->issued_at?->format('d/m/Y') }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3 border-t border-line pt-3">
                                <p class="min-w-0 truncate text-sm text-gray-700">{{ $invoice->service ?: 'Prestazione non indicata' }}</p>
                                <span class="inline-flex shrink-0 items-center gap-1.5 text-xs font-bold {{ $invoice->xml_downloaded_at ? 'text-emerald-700' : 'text-red-700' }}">
                                    @if ($invoice->xml_downloaded_at)
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m20 6-11 11-5-5" />
                                        </svg>
                                        Inviata
                                    @else
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M18 6 6 18M6 6l12 12" />
                                        </svg>
                                        Non inviata
                                    @endif
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="px-5 py-10 text-center text-gray-500">Nessuna fattura trovata.</div>
                    @endforelse
                </div>
                <div class="hidden overflow-x-auto md:block">
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

    <div class="invoice-export-modal hidden" aria-hidden="true" data-invoice-export-modal>
        <div class="invoice-export-dialog">
            <div class="border-b border-line bg-mist px-6 py-5">
                <p class="text-xs font-bold uppercase text-muted">Esportazione XML</p>
                <h3 class="mt-1 text-xl font-bold text-ink">Conferma esportazione</h3>
            </div>
            <div class="px-6 py-5">
                <p class="text-sm leading-6 text-gray-700">
                    Saranno esportate <strong class="text-ink">{{ $summary['count'] }}</strong> fatture dalla data
                    <strong class="text-ink">{{ $exportFrom }}</strong> alla data
                    <strong class="text-ink">{{ $exportTo }}</strong>.
                </p>
                <p class="mt-2 text-sm font-semibold text-muted">Il file verrà scaricato nella cartella download del browser.</p>
            </div>
            <div class="invoice-export-actions border-t border-line bg-white px-6 py-4">
                <button type="button" class="rounded-xl border border-line bg-white px-5 py-2.5 text-sm font-bold text-muted shadow-sm hover:bg-mist hover:text-ink" data-close-invoice-export-modal>
                    Annulla
                </button>
                <a href="{{ route('invoices.export-xml', $exportParams) }}" class="rounded-xl bg-sage px-5 py-2.5 text-center text-sm font-bold text-white shadow-sm hover:bg-[#4f7f75]">
                    Esporta
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('click', (event) => {
                const modal = document.querySelector('[data-invoice-export-modal]');

                if (event.target.closest('[data-open-invoice-export-modal]')) {
                    modal?.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('overflow-hidden');
                    return;
                }

                if (event.target.closest('[data-close-invoice-export-modal]') || event.target === modal) {
                    modal?.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('overflow-hidden');
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                const modal = document.querySelector('[data-invoice-export-modal]');
                modal?.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            });
        </script>
    @endpush
</x-app-layout>
