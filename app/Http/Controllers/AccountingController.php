<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\AccountingIncome;
use App\Models\AccountingIncomeSummary;
use App\Models\AccountingExpense;
use App\Models\Setting;
use App\Support\AccountingExpenseExcelImporter;
use App\Support\AccountingIncomeExcelImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountingController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $invoiceYears = Invoice::whereHas('patient', fn ($query) => $query->where('user_id', $userId))
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->map(fn ($year) => (int) $year)
            ->values();
        $incomeYears = AccountingIncome::where('user_id', $userId)->select('year')->distinct()->pluck('year')->map(fn ($year) => (int) $year);
        $summaryYears = AccountingIncomeSummary::where('user_id', $userId)->select('year')->distinct()->pluck('year')->map(fn ($year) => (int) $year);
        $expenseYears = AccountingExpense::where('user_id', $userId)->select('year')->distinct()->pluck('year')->map(fn ($year) => (int) $year);
        $years = $invoiceYears->merge($incomeYears)->merge($summaryYears)->merge($expenseYears)->unique()->sortDesc()->values();

        if ($years->isEmpty()) {
            $years = collect([(int) now()->year]);
        }

        $selectedYear = (int) $request->query('year', $years->first());
        if (! $years->contains($selectedYear)) {
            $selectedYear = (int) $years->first();
        }

        $invoices = Invoice::with('patient')
            ->whereHas('patient', fn ($query) => $query->where('user_id', $userId))
            ->where('year', $selectedYear)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->groupBy(fn (Invoice $invoice) => (int) $invoice->issued_at->format('n'));
        $expenses = AccountingExpense::where('user_id', $userId)
            ->where('year', $selectedYear)
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get()
            ->groupBy('month');
        $incomes = AccountingIncome::where('user_id', $userId)
            ->where('year', $selectedYear)
            ->orderBy('income_date')
            ->orderBy('id')
            ->get()
            ->groupBy('month');
        $incomeSummaries = AccountingIncomeSummary::where('user_id', $userId)
            ->where('year', $selectedYear)
            ->get()
            ->keyBy('month');

        $monthLabels = [
            1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
            5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
            9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre',
        ];

        $shortMonthLabels = [
            1 => 'Gen', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mag', 6 => 'Giu',
            7 => 'Lug', 8 => 'Ago', 9 => 'Set', 10 => 'Ott', 11 => 'Nov', 12 => 'Dic',
        ];

        $monthlyRows = collect(range(1, 12))->map(function (int $month) use ($invoices, $expenses, $incomes, $incomeSummaries, $monthLabels, $shortMonthLabels) {
            $monthInvoices = $invoices->get($month, collect());
            $monthExpenses = $expenses->get($month, collect());
            $monthIncomes = $incomes->get($month, collect());
            $summary = $incomeSummaries->get($month);
            $invoiced = $summary && $summary->invoiced_amount !== null
                ? (float) $summary->invoiced_amount
                : (float) $monthInvoices->sum('amount');
            $grossIncome = $summary && $summary->gross_income_amount !== null
                ? (float) $summary->gross_income_amount
                : (float) $monthIncomes->sum('amount');
            $totalIncome = $invoiced + $grossIncome;

            return [
                'month' => $month,
                'label' => $monthLabels[$month],
                'short_label' => $shortMonthLabels[$month],
                'invoice_count' => $monthInvoices->count(),
                'invoiced' => $invoiced,
                'gross_income' => $grossIncome,
                'total_income' => $totalIncome,
                'expenses' => (float) $monthExpenses->sum('amount'),
                'expense_count' => $monthExpenses->count(),
                'expense_details' => $monthExpenses->map(fn (AccountingExpense $expense) => [
                    'date' => $expense->expense_date?->format('d/m/Y') ?: 'Senza data',
                    'amount' => '€ '.number_format((float) $expense->amount, 2, ',', '.'),
                    'description' => $expense->description ?: 'Spesa senza causale',
                ])->values()->all(),
            ];
        });

        $maxRevenue = max(1, $monthlyRows->max('total_income'));

        $yearInvoiced = (float) $monthlyRows->sum('invoiced');
        $yearGrossIncome = (float) $monthlyRows->sum('gross_income');
        $yearTotalIncome = (float) $monthlyRows->sum('total_income');
        $taxSettings = $this->taxSettings();
        $flatRateCosts = $yearTotalIncome * $taxSettings['flat_rate_costs_rate'] / 100;
        $taxableIncome = max($yearTotalIncome - $flatRateCosts, 0);
        $tax15 = $taxableIncome * $taxSettings['tax_rate'] / 100;
        $inps = $taxableIncome * $taxSettings['inps_rate'] / 100;
        $taxesAndInps = $tax15 + $inps;
        $novemberTaxAdvance = $tax15 * $taxSettings['november_tax_advance_rate'] / 100;
        $novemberInpsAdvance = ($inps * $taxSettings['november_inps_advance_rate'] / 100) / max($taxSettings['november_inps_installments'], 1);
        $novemberAdvanceTotal = $novemberTaxAdvance + $novemberInpsAdvance;

        return view('accounting.index', [
            'years' => $years,
            'selectedYear' => $selectedYear,
            'monthlyRows' => $monthlyRows,
            'maxRevenue' => $maxRevenue,
            'yearInvoiced' => $yearInvoiced,
            'yearGrossIncome' => $yearGrossIncome,
            'yearTotalIncome' => $yearTotalIncome,
            'yearExpenses' => $monthlyRows->sum('expenses'),
            'taxSummary' => [
                'gross_total' => $yearTotalIncome,
                'flat_rate_costs' => $flatRateCosts,
                'taxable_income' => $taxableIncome,
                'tax_15' => $tax15,
                'inps' => $inps,
                'taxes_and_inps' => $taxesAndInps,
                'november_tax_advance' => $novemberTaxAdvance,
                'november_inps_advance' => $novemberInpsAdvance,
                'november_advance_total' => $novemberAdvanceTotal,
                'settings' => $taxSettings,
            ],
        ]);
    }

    public function importExpenses(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2035'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'expenses_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:51200'],
        ]);

        $created = AccountingExpenseExcelImporter::import(
            $validated['expenses_file'],
            (int) $validated['year'],
            (int) $validated['month']
        );

        return redirect()
            ->route('accounting.index', ['year' => $validated['year']])
            ->with('status', "Spese caricate: {$created} righe importate.");
    }

    public function importYearExpenses(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2035'],
            'expenses_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:51200'],
            'replace_existing' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('replace_existing')) {
            AccountingExpense::where('user_id', Auth::id())
                ->where('year', (int) $validated['year'])
                ->delete();
        }

        $created = AccountingExpenseExcelImporter::importYear(
            $validated['expenses_file'],
            (int) $validated['year']
        );

        return redirect()
            ->route('settings.accounting')
            ->with('status', "Spese {$validated['year']} caricate: {$created} righe importate.");
    }

    public function importYearIncomes(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2035'],
            'import_kind' => ['required', 'in:annual,gross'],
            'annual_incomes_file' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:51200', 'required_if:import_kind,annual'],
            'gross_incomes_file' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:51200', 'required_if:import_kind,gross'],
            'replace_existing' => ['nullable', 'boolean'],
        ]);

        $year = (int) $validated['year'];
        $isAnnualImport = $validated['import_kind'] === 'annual';

        if ($request->boolean('replace_existing') && $isAnnualImport) {
            AccountingIncomeSummary::where('user_id', Auth::id())->where('year', $year)->delete();
        }

        if ($request->boolean('replace_existing') && ! $isAnnualImport) {
            AccountingIncomeSummary::where('user_id', Auth::id())
                ->where('year', $year)
                ->update(['gross_income_amount' => null]);
        }

        $created = $isAnnualImport
            ? AccountingIncomeExcelImporter::importAnnualSummary($validated['annual_incomes_file'], $year)
            : AccountingIncomeExcelImporter::importGrossIncomeColumn($validated['gross_incomes_file'], $year);
        $label = $isAnnualImport ? 'Entrate annuali' : 'Entrate lorde';

        return redirect()
            ->route('settings.accounting')
            ->with('status', "{$label} {$year} caricate: {$created} righe importate.");
    }

    public function deleteExpenses(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2035'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $deleted = AccountingExpense::where('user_id', Auth::id())
            ->where('year', (int) $validated['year'])
            ->where('month', (int) $validated['month'])
            ->delete();

        return redirect()
            ->route('accounting.index', ['year' => $validated['year']])
            ->with('status', "Spese del mese cancellate: {$deleted} righe eliminate.");
    }

    private function taxSettings(): array
    {
        return [
            'accounting_tax_regime' => Setting::getValue('accounting_tax_regime', 'Regime forfettario'),
            'flat_rate_costs_rate' => (float) Setting::getValue('accounting_flat_rate_costs_rate', '22'),
            'tax_rate' => (float) Setting::getValue('accounting_tax_rate', '15'),
            'inps_rate' => (float) Setting::getValue('accounting_inps_rate', '25.98'),
            'november_tax_advance_rate' => (float) Setting::getValue('accounting_november_tax_advance_rate', '60'),
            'november_inps_advance_rate' => (float) Setting::getValue('accounting_november_inps_advance_rate', '80'),
            'november_inps_installments' => max((int) Setting::getValue('accounting_november_inps_installments', '2'), 1),
        ];
    }
}
