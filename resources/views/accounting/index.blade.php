<x-app-layout>
    <style>
        @media (max-width: 1100px) {
            .accounting-monthly-grid {
                grid-template-columns: 1fr !important;
            }
        }

        .accounting-icon {
            align-items: center;
            background: #eef7f4;
            border: 1px solid #c8d9d5;
            border-radius: 999px;
            color: #5f948a;
            display: inline-flex;
            height: 42px;
            justify-content: center;
            width: 42px;
        }
    </style>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Contabilita</h2>
                    <p class="mt-1 text-sm text-gray-500">Riepilogo contabile annuale con entrate, uscite e area imposte.</p>
                </div>
                <form method="GET" action="{{ route('accounting.index') }}" class="w-32">
                    <x-input-label for="year" value="Anno" class="text-[10px]" />
                    <select id="year" name="year" class="app-field mt-1 block w-full py-2 pr-8 text-sm font-bold" onchange="this.form.submit()">
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <a href="{{ route('settings.accounting') }}" class="w-full rounded-xl border border-line bg-white px-4 py-2.5 text-center text-sm font-bold text-ink shadow-sm hover:bg-mist sm:w-auto">
                Impostazioni contabilita
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ expensesModal: false, expenseInfoModal: false, expenseInfoTitle: '', expenseInfoRows: [], toInvoiceInfoModal: false, toInvoiceMonth: null }">
        <div class="app-section space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <section class="app-card overflow-hidden">
                <div class="grid gap-0 lg:grid-cols-[190px_minmax(0,1fr)]">
                    <aside class="border-b border-line bg-white p-4 lg:border-b-0 lg:border-r">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-base font-semibold leading-tight text-ink">{{ $chartLabel }}</p>
                            <p class="text-xl font-black text-[#0070c9]">€ {{ number_format($chartTotal, 2, ',', '.') }}</p>
                        </div>
                    </aside>

                    <div class="p-3 sm:p-4">
                        <div class="relative flex flex-wrap items-center justify-between gap-4">
                            <div class="flex flex-1 items-center justify-center">
                                <h3 class="text-xl font-black text-ink">{{ $chartLabel }} {{ $selectedYear }}</h3>
                                <span class="ml-2 inline-flex h-6 w-6 items-center justify-center rounded-full border-2 border-[#0070c9] text-sm font-black text-[#0070c9]">i</span>
                            </div>
                            <form method="GET" action="{{ route('accounting.index') }}" class="w-44">
                                <input type="hidden" name="year" value="{{ $selectedYear }}">
                                <x-input-label for="chart_metric" value="Visualizza" class="text-[10px]" />
                                <select id="chart_metric" name="chart_metric" class="app-field mt-1 block w-full py-2 pr-8 text-sm font-bold" onchange="this.form.submit()">
                                    <option value="invoiced" @selected($chartMetric === 'invoiced')>Fatturato</option>
                                    <option value="to_invoice" @selected($chartMetric === 'to_invoice')>Da fatturare</option>
                                    <option value="total_income" @selected($chartMetric === 'total_income')>Totale entrate</option>
                                </select>
                            </form>
                        </div>

                        <div class="mt-5 space-y-3 md:hidden">
                            @foreach ($monthlyRows as $row)
                                @php
                                    $mobileChartValue = (float) $row[$chartMetric];
                                    $mobileChartWidth = $mobileChartValue > 0 ? max(3, round(($mobileChartValue / $maxRevenue) * 100)) : 0;
                                @endphp
                                <div class="grid grid-cols-[34px_1fr_auto] items-center gap-2">
                                    <span class="text-xs font-bold text-muted">{{ $row['short_label'] }}</span>
                                    <div class="h-5 overflow-hidden rounded-sm border border-[#9bc4e2] bg-[#edf6fc]">
                                        <div class="h-full bg-[#73b4df]" style="width: {{ $mobileChartWidth }}%;"></div>
                                    </div>
                                    <span class="min-w-[82px] text-right text-xs font-bold text-ink">€ {{ number_format($mobileChartValue, 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="-mx-3 mt-5 hidden overflow-x-auto px-3 sm:mx-0 sm:px-0 md:block">
                        <div class="min-w-[680px] lg:min-w-0" style="display: grid; grid-template-columns: 52px minmax(0, 1fr); gap: 12px;">
                            <div style="height: 260px; position: relative; color: #6b7f7a; font-size: 11px; font-weight: 700;">
                                @foreach ([100, 75, 50, 25, 0] as $tick)
                                    @php
                                        $value = ($maxRevenue / 100) * $tick;
                                    @endphp
                                    <span style="position: absolute; right: 0; top: {{ 100 - $tick }}%; transform: translateY(-50%);">{{ number_format($value, 0, ',', '.') }}</span>
                                @endforeach
                            </div>

                            <div>
                                <div style="height: 260px; position: relative; display: flex; align-items: flex-end; gap: 18px; padding: 0 10px; border-left: 1px solid #6b7f7a; border-bottom: 1px solid #6b7f7a; background: linear-gradient(to top, transparent 0, transparent calc(25% - 1px), #d6dfdc 25%, transparent calc(25% + 1px), transparent calc(50% - 1px), #d6dfdc 50%, transparent calc(50% + 1px), transparent calc(75% - 1px), #d6dfdc 75%, transparent calc(75% + 1px));">
                                    @foreach ($monthlyRows as $row)
                                        @php
                                            $chartValue = (float) $row[$chartMetric];
                                            $barHeight = $chartValue > 0 ? max(10, round(($chartValue / $maxRevenue) * 238)) : 0;
                                        @endphp
                                        <div style="height: 100%; flex: 1; min-width: 0; display: flex; align-items: flex-end; justify-content: center;">
                                            <div style="height: {{ $barHeight }}px; width: 70%; max-width: 58px; min-width: 22px; border: 2px solid #0070c9; background: #b7d7ee;" title="{{ $row['label'] }}: € {{ number_format($chartValue, 2, ',', '.') }}"></div>
                                        </div>
                                    @endforeach
                                </div>

                                <div style="display: flex; gap: 18px; padding: 8px 10px 0;">
                                    @foreach ($monthlyRows as $row)
                                        <span style="flex: 1; min-width: 0; text-align: center; font-size: 11px; font-weight: 700; color: #17312d;">{{ $row['short_label'] }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        </div>

                        <div class="mt-4 hidden items-center justify-center gap-2 text-xs font-semibold text-ink md:flex">
                            <span class="inline-block h-3 w-3 border border-[#0070c9] bg-[#dceeff]"></span>
                            {{ $chartLabel }}
                        </div>
                    </div>
                </div>
            </section>

            <section class="accounting-monthly-grid" style="display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(360px, .65fr); gap: 24px; align-items: stretch;">
                <div class="app-card overflow-hidden" style="display: flex; flex-direction: column; height: 100%; background: #e4f7ed;">
                    <div class="flex items-center gap-3 border-b border-line px-6 py-4" style="background: #d4f0e1;">
                        <span class="accounting-icon">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Entrate</p>
                            <h3 class="mt-1 text-lg font-semibold text-gray-900">Riepilogo mensile</h3>
                        </div>
                    </div>
                    <div class="divide-y divide-line md:hidden" style="background: #eefaf4;">
                        @foreach ($monthlyRows as $row)
                            <div class="p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-bold text-ink">{{ $row['label'] }}</p>
                                    <p class="font-bold text-sage">€ {{ number_format($row['total_income'], 2, ',', '.') }}</p>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                    <div class="rounded-lg border border-line bg-white/70 p-2.5">
                                        <p class="font-bold uppercase text-muted">Fatturato</p>
                                        <p class="mt-1 font-semibold text-ink">€ {{ number_format($row['invoiced'], 2, ',', '.') }}</p>
                                    </div>
                                    <div class="rounded-lg border border-line bg-white/70 p-2.5">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="font-bold uppercase text-muted">Da fatturare</p>
                                            <button
                                                type="button"
                                                class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-line bg-white text-xs font-black text-sage"
                                                @click="toInvoiceMonth = {{ $row['month'] }}; toInvoiceInfoModal = true"
                                            >i</button>
                                        </div>
                                        <p class="mt-1 font-semibold text-ink">€ {{ number_format($row['to_invoice'], 2, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="flex items-center justify-between gap-3 p-4 font-black" style="background: #d4f0e1;">
                            <span>Totale</span>
                            <span class="text-sage">€ {{ number_format($yearTotalIncome, 2, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="hidden overflow-x-auto md:block" style="width: 100%; flex: 1;">
                        <table class="text-sm" style="width: 100%; table-layout: fixed;">
                            <thead class="bg-mist text-xs font-bold uppercase text-muted">
                                <tr style="height: 42px;">
                                    <th class="px-5 text-left" style="width: 22%;">Mese</th>
                                    <th class="px-5 text-right" style="width: 24%;">Fatturato</th>
                                    <th class="px-5 text-right" style="width: 24%;">Da fatturare</th>
                                    <th class="px-3 text-right" style="width: 8%;">Info</th>
                                    <th class="px-5 text-right" style="width: 22%;">Totale entrate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line" style="background: #eefaf4;">
                                @foreach ($monthlyRows as $row)
                                    <tr style="height: 45px;">
                                        <td class="px-5 font-bold text-ink">{{ $row['label'] }}</td>
                                        <td class="px-5 text-right font-semibold text-ink">€ {{ number_format($row['invoiced'], 2, ',', '.') }}</td>
                                        <td class="px-5 text-right font-semibold text-muted">€ {{ number_format($row['to_invoice'], 2, ',', '.') }}</td>
                                        <td class="px-3 text-right">
                                            <button
                                                type="button"
                                                class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-line bg-white text-xs font-black text-sage shadow-sm hover:bg-mist"
                                                @click="toInvoiceMonth = {{ $row['month'] }}; toInvoiceInfoModal = true"
                                            >i</button>
                                        </td>
                                        <td class="px-5 text-right font-semibold text-sage">€ {{ number_format($row['total_income'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-line font-bold" style="background: #d4f0e1;">
                                <tr style="height: 45px;">
                                    <td class="px-5">Totale</td>
                                    <td class="px-5 text-right">€ {{ number_format($yearInvoiced, 2, ',', '.') }}</td>
                                    <td class="px-5 text-right text-muted">€ {{ number_format($yearToInvoice, 2, ',', '.') }}</td>
                                    <td class="px-3"></td>
                                    <td class="px-5 text-right text-sage">€ {{ number_format($yearTotalIncome, 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="app-card overflow-hidden" style="display: flex; flex-direction: column; height: 100%; background: #ffe8e8;">
                    <div class="flex items-center justify-between gap-3 border-b border-line px-6 py-4" style="background: #ffd7d7;">
                        <div class="flex items-center gap-3">
                            <span class="accounting-icon">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </span>
                            <div>
                                <p class="text-xs font-bold uppercase text-muted">Uscite</p>
                                <h3 class="mt-1 text-lg font-semibold text-gray-900">Spese</h3>
                            </div>
                        </div>
                        <button type="button" class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-ink shadow-sm hover:bg-mist" @click="expensesModal = true">Carica spese</button>
                    </div>
                    <div class="divide-y divide-line md:hidden" style="background: #fff0f0;">
                        @foreach ($monthlyRows as $row)
                            <div class="flex items-center justify-between gap-3 p-4">
                                <p class="font-bold text-ink">{{ $row['label'] }}</p>
                                <div class="flex items-center gap-3">
                                    <p class="font-semibold text-ink">€ {{ number_format($row['expenses'], 2, ',', '.') }}</p>
                                    <button
                                        type="button"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-line bg-white text-xs font-black text-sage"
                                        @click='expenseInfoTitle = @json($row["label"]); expenseInfoRows = @json($row["expense_details"]); expenseInfoModal = true'
                                    >i</button>
                                </div>
                            </div>
                        @endforeach
                        <div class="flex items-center justify-between gap-3 p-4 font-black" style="background: #ffd7d7;">
                            <span>Totale</span>
                            <span>€ {{ number_format($yearExpenses, 2, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="hidden overflow-x-auto md:block" style="width: 100%; flex: 1;">
                        <table class="text-sm" style="width: 100%; table-layout: fixed;">
                            <thead class="bg-mist text-xs font-bold uppercase text-muted">
                                <tr style="height: 42px;">
                                    <th class="px-4 text-left" style="width: 42%;">Mese</th>
                                    <th class="px-4 text-right" style="width: 38%;">Uscite</th>
                                    <th class="px-4 text-right" style="width: 20%;">Info</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line" style="background: #fff0f0;">
                                @foreach ($monthlyRows as $row)
                                    <tr style="height: 45px;">
                                        <td class="px-4 font-bold text-ink">{{ $row['label'] }}</td>
                                        <td class="px-4 text-right font-semibold text-ink">€ {{ number_format($row['expenses'], 2, ',', '.') }}</td>
                                        <td class="px-4 text-right">
                                            <button
                                                type="button"
                                                class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-line bg-white text-xs font-black text-sage shadow-sm hover:bg-mist"
                                                @click='expenseInfoTitle = @json($row["label"]); expenseInfoRows = @json($row["expense_details"]); expenseInfoModal = true'
                                            >i</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-line font-bold" style="background: #ffd7d7;">
                                <tr style="height: 45px;">
                                    <td class="px-4">Totale</td>
                                    <td class="px-4 text-right">€ {{ number_format($yearExpenses, 2, ',', '.') }}</td>
                                    <td class="px-4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </section>

            <section class="app-card p-4 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="accounting-icon">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6"/><path d="M9 18h6"/><path d="M9 12h2"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Imposte</p>
                            <h3 class="mt-1 text-lg font-semibold text-gray-900">{{ $taxSummary['settings']['accounting_tax_regime'] }} {{ $selectedYear }}</h3>
                        </div>
                    </div>
                    <span class="rounded-full border border-line bg-mist px-3 py-1.5 text-xs font-bold text-sage">Aliquote da Excel</span>
                </div>

                <div class="mt-5 grid gap-5 xl:grid-cols-[1fr_.8fr]">
                    <div class="overflow-hidden rounded-xl border border-line bg-white">
                        <div class="bg-[#f5bf8e] px-5 py-2 text-center text-sm font-black text-ink">{{ $taxSummary['settings']['accounting_tax_regime'] ?? 'Regime forfettario' }}</div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-line">
                                <tr>
                                    <td class="px-5 py-3 font-bold text-ink">Tot. fatturato lordo</td>
                                    <td class="px-5 py-3 text-right font-bold text-ink">€ {{ number_format($taxSummary['gross_total'], 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-muted">Forfait spese {{ number_format($taxSummary['settings']['flat_rate_costs_rate'], 2, ',', '.') }}%</td>
                                    <td class="px-5 py-3 text-right font-semibold text-ink">€ {{ number_format($taxSummary['flat_rate_costs'], 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-muted">Reddito imponibile</td>
                                    <td class="px-5 py-3 text-right font-semibold text-ink">€ {{ number_format($taxSummary['taxable_income'], 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-muted">Tasse {{ number_format($taxSummary['settings']['tax_rate'], 2, ',', '.') }}%</td>
                                    <td class="px-5 py-3 text-right font-semibold text-ink">€ {{ number_format($taxSummary['tax_15'], 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-muted">INPS {{ number_format($taxSummary['settings']['inps_rate'], 2, ',', '.') }}%</td>
                                    <td class="px-5 py-3 text-right font-semibold text-ink">€ {{ number_format($taxSummary['inps'], 2, ',', '.') }}</td>
                                </tr>
                            </tbody>
                            <tfoot style="background: #f5bf8e; border-top: 4px solid #17312d;">
                                <tr>
                                    <td class="px-5 py-4 text-base font-black text-ink">Tot. tasse + INPS</td>
                                    <td class="px-5 py-4 text-right text-base font-black text-ink">€ {{ number_format($taxSummary['taxes_and_inps'], 2, ',', '.') }}</td>
                                </tr>
                                <tr style="background: #fff7ed;">
                                    <td class="px-5 py-4 text-base font-black text-ink">Acconto novembre {{ $taxSummary['previous_year'] }}</td>
                                    <td class="px-5 py-4 text-right text-base font-black text-ink">€ {{ number_format($taxSummary['previous_year_november_advance'], 2, ',', '.') }}</td>
                                </tr>
                                <tr style="background: #f5bf8e; border-top: 4px solid #17312d;">
                                    <td class="px-5 py-4 text-base font-black text-ink">Tot. tasse Luglio</td>
                                    <td class="px-5 py-4 text-right text-base font-black text-ink">€ {{ number_format($taxSummary['july_taxes_total'], 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-line bg-white">
                        <div class="bg-[#cfc2de] px-5 py-2 text-center text-sm font-black text-ink">Acconto novembre</div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-line">
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-muted">TAX {{ number_format($taxSummary['settings']['november_tax_advance_rate'], 2, ',', '.') }}%</td>
                                    <td class="px-5 py-3 text-right font-bold text-ink">€ {{ number_format($taxSummary['november_tax_advance'], 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-muted">INPS {{ number_format($taxSummary['settings']['november_inps_advance_rate'], 2, ',', '.') }}% / {{ $taxSummary['settings']['november_inps_installments'] }}</td>
                                    <td class="px-5 py-3 text-right font-bold text-ink">€ {{ number_format($taxSummary['november_inps_advance'], 2, ',', '.') }}</td>
                                </tr>
                            </tbody>
                            <tfoot style="background: #cfc2de; border-top: 4px solid #17312d;">
                                <tr>
                                    <td class="px-5 py-4 text-base font-black text-ink">Tot. acconto novembre</td>
                                    <td class="px-5 py-4 text-right text-base font-black text-ink">€ {{ number_format($taxSummary['november_advance_total'], 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-line bg-mist px-5 py-4 text-xs font-semibold text-muted">
                    Calcolo da impostazioni: forfait spese = fatturato lordo x {{ number_format($taxSummary['settings']['flat_rate_costs_rate'], 2, ',', '.') }}%; tasse = imponibile x {{ number_format($taxSummary['settings']['tax_rate'], 2, ',', '.') }}%; INPS = imponibile x {{ number_format($taxSummary['settings']['inps_rate'], 2, ',', '.') }}%; acconto novembre = {{ number_format($taxSummary['settings']['november_tax_advance_rate'], 2, ',', '.') }}% tasse + quota INPS configurata.
                </div>
            </section>

            <div x-cloak x-show="expensesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/45 p-0 sm:p-4">
                <div class="h-full max-h-dvh w-full max-w-2xl overflow-y-auto bg-white p-4 shadow-2xl sm:h-auto sm:max-h-[92vh] sm:rounded-2xl sm:border sm:border-line sm:p-6" @click.outside="expensesModal = false">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Uscite</p>
                            <h3 class="mt-1 text-xl font-bold text-ink">Carica spese mensili</h3>
                        </div>
                        <button type="button" class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-ink hover:bg-mist" @click="expensesModal = false">Chiudi</button>
                    </div>

                    <form method="POST" action="{{ route('accounting.expenses.import') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="expense_import_month" value="Mese da modificare" />
                                <select id="expense_import_month" name="month" class="app-field mt-1 block w-full" required>
                                    @foreach ($monthlyRows as $row)
                                        <option value="{{ $row['month'] }}">{{ $row['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="expenses_file" value="File Excel spese" />
                                <input id="expenses_file" name="expenses_file" type="file" accept=".xlsx,.xls" class="app-field mt-1 block w-full" required>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <x-primary-button>Carica Excel</x-primary-button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('accounting.expenses.delete') }}" class="mt-6 rounded-xl border border-rose-100 bg-rose-50 p-4">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <div class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                            <div>
                                <x-input-label for="expense_delete_month" value="Cancella spese del mese" />
                                <select id="expense_delete_month" name="month" class="app-field mt-1 block w-full" required>
                                    @foreach ($monthlyRows as $row)
                                        <option value="{{ $row['month'] }}">{{ $row['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm font-bold text-rose-700 shadow-sm hover:bg-rose-100">
                                Cancella spese mese
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-cloak x-show="toInvoiceInfoModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/45 p-0 sm:p-4">
                <div class="h-full max-h-dvh w-full max-w-4xl overflow-y-auto bg-white p-4 shadow-2xl sm:h-auto sm:max-h-[92vh] sm:rounded-2xl sm:border sm:border-line sm:p-6" @click.outside="toInvoiceInfoModal = false">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Da fatturare</p>
                            <template x-for="month in [toInvoiceMonth]" :key="month">
                                <h3 class="mt-1 text-xl font-bold text-ink">
                                    @foreach ($monthlyRows as $row)
                                        <span x-show="month === {{ $row['month'] }}">{{ $row['label'] }} {{ $selectedYear }}</span>
                                    @endforeach
                                </h3>
                            </template>
                        </div>
                        <button type="button" class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-ink hover:bg-mist" @click="toInvoiceInfoModal = false">Chiudi</button>
                    </div>

                    @foreach ($monthlyRows as $row)
                        <div x-show="toInvoiceMonth === {{ $row['month'] }}" class="mt-5 overflow-x-auto rounded-xl border border-line">
                            <table class="min-w-[720px] text-sm">
                                <thead class="bg-mist text-xs font-bold uppercase text-muted">
                                    <tr>
                                        <th class="px-4 py-3 text-left" style="width: 18%;">Data</th>
                                        <th class="px-4 py-3 text-left" style="width: 32%;">Paziente</th>
                                        <th class="px-4 py-3 text-left" style="width: 32%;">Importo calcolato</th>
                                        <th class="px-4 py-3 text-right" style="width: 18%;">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line bg-white">
                                    @forelse ($row['to_invoice_sessions'] as $session)
                                        @php
                                            $currentFee = (float) ($session->fee ?? 0);
                                            $hasCurrentFeeInRates = collect($sessionRates)->contains(fn ($rate) => abs((float) ($rate['amount'] ?? 0) - $currentFee) < 0.01);
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3 font-semibold text-ink">{{ $session->session_date?->format('d/m/Y') }}</td>
                                            <td class="px-4 py-3 font-semibold text-ink">{{ $session->patient?->list_name ?: 'Paziente non indicato' }}</td>
                                            <td class="px-4 py-3">
                                                <form id="update-to-invoice-session-{{ $session->id }}" method="POST" action="{{ route('patients.sessions.update', [$session->patient_id, $session]) }}" class="flex items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="session_date" value="{{ $session->session_date?->toDateString() }}">
                                                    <input type="hidden" name="title" value="{{ $session->title ?: 'Seduta osteopatica' }}">
                                                    <input type="hidden" name="paid" value="0">
                                                    <span class="relative inline-flex min-w-[165px]">
                                                        <select name="fee" class="app-field w-full appearance-none py-2 pl-4 pr-12 text-sm">
                                                            @unless ($hasCurrentFeeInRates)
                                                                <option value="{{ number_format($currentFee, 2, '.', '') }}" selected>€ {{ number_format($currentFee, 2, ',', '.') }}</option>
                                                            @endunless
                                                            @foreach ($sessionRates as $rate)
                                                                @php $rateAmount = (float) ($rate['amount'] ?? 0); @endphp
                                                                <option value="{{ number_format($rateAmount, 2, '.', '') }}" @selected(abs($rateAmount - $currentFee) < 0.01)>
                                                                    € {{ number_format($rateAmount, 2, ',', '.') }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sage">
                                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                                            </svg>
                                                        </span>
                                                    </span>
                                                    <button class="rounded-xl bg-sage px-3 py-2 text-xs font-bold text-white hover:bg-[#4f7f75]">Salva</button>
                                                </form>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <form method="POST" action="{{ route('patients.sessions.destroy', [$session->patient_id, $session]) }}" onsubmit="return confirm('Cancellare questa seduta dal conteggio?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50">Cancella</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-8 text-center text-sm font-semibold text-muted">
                                                Nessuna seduta da fatturare per questo mese.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                            @if (($row['manual_to_invoice'] ?? 0) > 0)
                                <div class="border-t border-line bg-mist px-4 py-3 text-xs font-semibold text-muted">
                                    In questo mese sono presenti anche € {{ number_format($row['manual_to_invoice'], 2, ',', '.') }} inseriti/importati manualmente.
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div x-cloak x-show="expenseInfoModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/45 p-0 sm:p-4">
                <div class="h-full max-h-dvh w-full max-w-2xl overflow-y-auto bg-white p-4 shadow-2xl sm:h-auto sm:max-h-[92vh] sm:rounded-2xl sm:border sm:border-line sm:p-6" @click.outside="expenseInfoModal = false">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Dettaglio spese</p>
                            <h3 class="mt-1 text-xl font-bold text-ink" x-text="expenseInfoTitle"></h3>
                        </div>
                        <button type="button" class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-ink hover:bg-mist" @click="expenseInfoModal = false">Chiudi</button>
                    </div>
                    <div class="mt-5 overflow-x-auto rounded-xl border border-line">
                        <table class="min-w-[520px] text-sm">
                            <thead class="bg-mist text-xs font-bold uppercase text-muted">
                                <tr>
                                    <th class="px-4 py-3 text-left">Data</th>
                                    <th class="px-4 py-3 text-right">Entita spesa</th>
                                    <th class="px-4 py-3 text-left">Causale</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line bg-white">
                                <template x-if="expenseInfoRows.length === 0">
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-sm font-semibold text-muted">Nessuna spesa caricata per questo mese.</td>
                                    </tr>
                                </template>
                                <template x-for="(expense, index) in expenseInfoRows" :key="index">
                                    <tr>
                                        <td class="px-4 py-3 font-semibold text-ink" x-text="expense.date"></td>
                                        <td class="px-4 py-3 text-right font-bold text-ink" x-text="expense.amount"></td>
                                        <td class="px-4 py-3 text-muted" x-text="expense.description"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>




