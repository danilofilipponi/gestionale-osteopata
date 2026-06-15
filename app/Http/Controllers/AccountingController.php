<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\AccountingExpense;
use App\Support\AccountingExpenseExcelImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountingController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $years = Invoice::whereHas('patient', fn ($query) => $query->where('user_id', $userId))
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->map(fn ($year) => (int) $year)
            ->values();

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

        $monthLabels = [
            1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
            5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
            9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre',
        ];

        $shortMonthLabels = [
            1 => 'Gen', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mag', 6 => 'Giu',
            7 => 'Lug', 8 => 'Ago', 9 => 'Set', 10 => 'Ott', 11 => 'Nov', 12 => 'Dic',
        ];

        $monthlyRows = collect(range(1, 12))->map(function (int $month) use ($invoices, $expenses, $monthLabels, $shortMonthLabels) {
            $monthInvoices = $invoices->get($month, collect());
            $monthExpenses = $expenses->get($month, collect());
            $invoiced = (float) $monthInvoices->sum('amount');
            $grossIncome = 0.0;
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
                    'amount' => 'EUR '.number_format((float) $expense->amount, 2, ',', '.'),
                    'description' => $expense->description ?: 'Spesa senza causale',
                ])->values()->all(),
            ];
        });

        $maxRevenue = max(1, $monthlyRows->max('total_income'));

        $yearInvoiced = (float) $monthlyRows->sum('invoiced');
        $yearGrossIncome = (float) $monthlyRows->sum('gross_income');
        $yearTotalIncome = (float) $monthlyRows->sum('total_income');
        $flatRateCosts = $yearTotalIncome * 22 / 100;
        $taxableIncome = max($yearTotalIncome - $flatRateCosts, 0);
        $tax15 = $taxableIncome * 15 / 100;
        $inps = $taxableIncome * 25.98 / 100;
        $taxesAndInps = $tax15 + $inps;
        $novemberTaxAdvance = $tax15 * 60 / 100;
        $novemberInpsAdvance = ($inps * 80 / 100) / 2;
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
            ],
        ]);
    }

    public function importExpenses(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2035'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'expenses_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
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
}
