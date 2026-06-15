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
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Contabilita</h2>
                <p class="mt-1 text-sm text-gray-500">Riepilogo contabile annuale con entrate, uscite e area imposte.</p>
            </div>
            <a href="{{ route('settings.accounting') }}" class="rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist">
                Impostazioni contabilita
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ expensesModal: false, expenseInfoModal: false, expenseInfoTitle: '', expenseInfoRows: [] }">
        <div class="app-section space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <section class="app-card p-6">
                <form method="GET" action="{{ route('accounting.index') }}" class="max-w-xs">
                    <x-input-label for="year" value="Anno contabile" />
                    <select id="year" name="year" class="app-field mt-1 block w-full" onchange="this.form.submit()">
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </form>
            </section>

            <section class="app-card overflow-hidden">
                <div class="grid gap-0 lg:grid-cols-[190px_minmax(0,1fr)]">
                    <aside class="border-b border-line bg-white p-4 lg:border-b-0 lg:border-r">
                        <div class="inline-flex overflow-hidden rounded-t-md border-b border-sage text-xs font-bold">
                            <span class="bg-white px-3 py-2 text-ink">Mese</span>
                            <span class="bg-gray-200 px-3 py-2 text-muted">Trimestre</span>
                        </div>

                        <div class="mt-10">
                            <p class="text-base font-semibold leading-tight text-ink">Totale<br>entrate</p>
                            <p class="mt-3 text-xl font-black text-[#0070c9]">EUR {{ number_format($yearTotalIncome, 2, ',', '.') }}</p>
                        </div>
                    </aside>

                    <div class="p-4">
                        <div class="relative flex items-center justify-center">
                            <h3 class="text-xl font-black text-ink">Fatturato {{ $selectedYear }}</h3>
                            <span class="ml-2 inline-flex h-6 w-6 items-center justify-center rounded-full border-2 border-[#0070c9] text-sm font-black text-[#0070c9]">i</span>
                        </div>

                        <div class="mt-5 grid grid-cols-[44px_1fr] gap-3">
                            <div class="relative h-64 text-[11px] font-semibold text-muted">
                                @foreach ([100, 75, 50, 25, 0] as $tick)
                                    @php
                                        $value = ($maxRevenue / 100) * $tick;
                                    @endphp
                                    <span class="absolute right-0 -translate-y-1/2" style="top: {{ 100 - $tick }}%">{{ number_format($value, 0, ',', '.') }}</span>
                                @endforeach
                            </div>

                            <div class="relative h-64 overflow-hidden border-b border-l border-gray-400">
                                @foreach ([0, 25, 50, 75, 100] as $line)
                                    <div class="absolute left-0 right-0 border-t border-gray-300" style="bottom: {{ $line }}%"></div>
                                @endforeach

                                <div class="absolute inset-x-0 bottom-0 z-10 flex h-64 items-end gap-5 px-2">
                                    @foreach ($monthlyRows as $row)
                                        @php
                                            $barHeight = $row['total_income'] > 0 ? max(8, round(($row['total_income'] / $maxRevenue) * 236)) : 0;
                                        @endphp
                                        <div class="flex h-full min-w-0 flex-1 flex-col items-center justify-end">
                                            <div style="height: {{ $barHeight }}px; width: 100%; max-width: 54px; min-width: 24px; border: 2px solid #0070c9; background: #b7d7ee;" title="{{ $row['label'] }}: EUR {{ number_format($row['total_income'], 2, ',', '.') }}"></div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div></div>
                            <div class="flex gap-4 px-1 pt-2">
                                @foreach ($monthlyRows as $row)
                                    <span class="min-w-0 flex-1 text-center text-[11px] font-semibold text-ink">{{ $row['short_label'] }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-center gap-2 text-xs font-semibold text-ink">
                            <span class="inline-block h-3 w-3 border border-[#0070c9] bg-[#dceeff]"></span>
                            Totale entrate
                        </div>
                    </div>
                </div>
            </section>

            <section class="accounting-monthly-grid" style="display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(360px, .65fr); gap: 24px; align-items: start;">
                <div class="app-card overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-line bg-white px-6 py-4">
                        <span class="accounting-icon">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Entrate</p>
                            <h3 class="mt-1 text-lg font-semibold text-gray-900">Riepilogo mensile</h3>
                        </div>
                    </div>
                    <div class="overflow-x-auto" style="width: 100%;">
                        <table class="text-sm" style="width: 100%; table-layout: fixed;">
                            <thead class="bg-mist text-xs font-bold uppercase text-muted">
                                <tr>
                                    <th class="px-5 py-3 text-left" style="width: 25%;">Mese</th>
                                    <th class="px-5 py-3 text-right" style="width: 25%;">Fatturato</th>
                                    <th class="px-5 py-3 text-right" style="width: 25%;">Entrate lorde</th>
                                    <th class="px-5 py-3 text-right" style="width: 25%;">Totale entrate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line bg-white">
                                @foreach ($monthlyRows as $row)
                                    <tr>
                                        <td class="px-5 py-3 font-bold text-ink">{{ $row['label'] }}</td>
                                        <td class="px-5 py-3 text-right font-semibold text-ink">EUR {{ number_format($row['invoiced'], 2, ',', '.') }}</td>
                                        <td class="px-5 py-3 text-right font-semibold text-muted">EUR {{ number_format($row['gross_income'], 2, ',', '.') }}</td>
                                        <td class="px-5 py-3 text-right font-semibold text-sage">EUR {{ number_format($row['total_income'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-line bg-mist font-bold">
                                <tr>
                                    <td class="px-5 py-3">Totale</td>
                                    <td class="px-5 py-3 text-right">EUR {{ number_format($yearInvoiced, 2, ',', '.') }}</td>
                                    <td class="px-5 py-3 text-right text-muted">EUR {{ number_format($yearGrossIncome, 2, ',', '.') }}</td>
                                    <td class="px-5 py-3 text-right text-sage">EUR {{ number_format($yearTotalIncome, 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="app-card overflow-hidden">
                    <div class="flex items-center justify-between gap-3 border-b border-line bg-white px-6 py-4">
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
                    <div class="overflow-x-auto" style="width: 100%;">
                        <table class="text-sm" style="width: 100%; table-layout: fixed;">
                            <thead class="bg-mist text-xs font-bold uppercase text-muted">
                                <tr>
                                    <th class="px-4 py-3 text-left" style="width: 42%;">Mese</th>
                                    <th class="px-4 py-3 text-right" style="width: 38%;">Uscite</th>
                                    <th class="px-4 py-3 text-right" style="width: 20%;">Info</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line bg-white">
                                @foreach ($monthlyRows as $row)
                                    <tr>
                                        <td class="px-4 py-3 font-bold text-ink">{{ $row['label'] }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-ink">EUR {{ number_format($row['expenses'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <button
                                                type="button"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-line bg-white text-sm font-black text-sage shadow-sm hover:bg-mist"
                                                @click='expenseInfoTitle = @json($row["label"]); expenseInfoRows = @json($row["expense_details"]); expenseInfoModal = true'
                                            >i</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-line bg-mist font-bold">
                                <tr>
                                    <td class="px-4 py-3">Totale</td>
                                    <td class="px-4 py-3 text-right">EUR {{ number_format($yearExpenses, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </section>

            <section class="app-card p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="accounting-icon">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6"/><path d="M9 18h6"/><path d="M9 12h2"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Imposte</p>
                            <h3 class="mt-1 text-lg font-semibold text-gray-900">Regime forfettario {{ $selectedYear }}</h3>
                        </div>
                    </div>
                    <span class="rounded-full border border-line bg-mist px-3 py-1.5 text-xs font-bold text-sage">Aliquote da Excel</span>
                </div>

                <div class="mt-5 grid gap-5 xl:grid-cols-[1fr_.8fr]">
                    <div class="overflow-hidden rounded-xl border border-line bg-white">
                        <div class="bg-[#f5bf8e] px-5 py-2 text-center text-sm font-black text-ink">Regime forfettario</div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-line">
                                <tr>
                                    <td class="px-5 py-3 font-bold text-ink">Tot. fatturato lordo</td>
                                    <td class="px-5 py-3 text-right font-bold text-ink">EUR {{ number_format($taxSummary['gross_total'], 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-muted">Forfait spese 22%</td>
                                    <td class="px-5 py-3 text-right font-semibold text-ink">EUR {{ number_format($taxSummary['flat_rate_costs'], 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-muted">Reddito imponibile</td>
                                    <td class="px-5 py-3 text-right font-semibold text-ink">EUR {{ number_format($taxSummary['taxable_income'], 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-muted">Tasse 15%</td>
                                    <td class="px-5 py-3 text-right font-semibold text-ink">EUR {{ number_format($taxSummary['tax_15'], 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-muted">INPS 25,98%</td>
                                    <td class="px-5 py-3 text-right font-semibold text-ink">EUR {{ number_format($taxSummary['inps'], 2, ',', '.') }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="border-t-2 border-ink bg-[#f5bf8e]">
                                <tr>
                                    <td class="px-5 py-3 font-black text-ink">Tot. tasse + INPS</td>
                                    <td class="px-5 py-3 text-right font-black text-ink">EUR {{ number_format($taxSummary['taxes_and_inps'], 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-line bg-white">
                        <div class="bg-[#cfc2de] px-5 py-2 text-center text-sm font-black text-ink">Acconto novembre</div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-line">
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-muted">TAX 60%</td>
                                    <td class="px-5 py-3 text-right font-bold text-ink">EUR {{ number_format($taxSummary['november_tax_advance'], 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-muted">INPS 80% / 2</td>
                                    <td class="px-5 py-3 text-right font-bold text-ink">EUR {{ number_format($taxSummary['november_inps_advance'], 2, ',', '.') }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="border-t-2 border-ink bg-[#cfc2de]">
                                <tr>
                                    <td class="px-5 py-3 font-black text-ink">Tot. acconto novembre</td>
                                    <td class="px-5 py-3 text-right font-black text-ink">EUR {{ number_format($taxSummary['november_advance_total'], 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-line bg-mist px-5 py-4 text-xs font-semibold text-muted">
                    Calcolo da Excel: forfait spese = fatturato lordo x 22%; tasse = imponibile x 15%; INPS = imponibile x 25,98%; acconto novembre = 60% tasse + meta dell'80% INPS.
                </div>
            </section>

            <div x-cloak x-show="expensesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/45 p-4">
                <div class="w-full max-w-2xl rounded-2xl border border-line bg-white p-6 shadow-2xl" @click.outside="expensesModal = false">
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

            <div x-cloak x-show="expenseInfoModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/45 p-4">
                <div class="w-full max-w-2xl rounded-2xl border border-line bg-white p-6 shadow-2xl" @click.outside="expenseInfoModal = false">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Dettaglio spese</p>
                            <h3 class="mt-1 text-xl font-bold text-ink" x-text="expenseInfoTitle"></h3>
                        </div>
                        <button type="button" class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-ink hover:bg-mist" @click="expenseInfoModal = false">Chiudi</button>
                    </div>
                    <div class="mt-5 overflow-hidden rounded-xl border border-line">
                        <table class="min-w-full text-sm">
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
