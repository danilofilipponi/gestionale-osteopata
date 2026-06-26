<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:all,draft,sent,paid,cancelled'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'summary_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'summary_month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $availableYears = $this->availableYears();
        $selectedYear = (int) ($validated['summary_year'] ?? now()->year);
        $selectedMonth = (int) ($validated['summary_month'] ?? now()->month);
        $availableMonths = $this->availableMonths($selectedYear);
        $validated['summary_year'] = $selectedYear;
        $validated['summary_month'] = $selectedMonth;

        $query = $this->invoiceQuery($validated);

        $summary = [
            'count' => (clone $query)->count(),
            'total' => $this->yearlyTotal($selectedYear),
            'paid' => $this->monthlyPaidTotal($selectedYear, $selectedMonth),
            'open' => (clone $query)->whereIn('status', ['draft', 'sent'])->sum('amount'),
        ];

        $statusCounts = $this->invoiceQuery($validated, false)
            ->selectRaw('status, count(*) as count, sum(amount) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $invoices = $query
            ->orderByDesc('year')
            ->orderByDesc('progressive_number')
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'summary' => $summary,
            'statusCounts' => $statusCounts,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'status' => $validated['status'] ?? 'all',
                'from' => $validated['from'] ?? '',
                'to' => $validated['to'] ?? '',
                'summary_year' => $selectedYear,
                'summary_month' => $selectedMonth,
            ],
            'statuses' => $this->statuses(),
            'availableYears' => $availableYears,
            'availableMonths' => $availableMonths,
            'monthLabels' => $this->monthLabels(),
        ]);
    }

    private function invoiceQuery(array $filters, bool $includeStatus = true)
    {
        $hasSearch = filled($filters['search'] ?? null);

        return Invoice::with('patient')
            ->whereHas('patient', fn ($query) => $query->where('user_id', Auth::id()))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('number', 'like', "%{$search}%")
                        ->orWhere('service', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('fiscal_code', 'like', "%{$search}%");
                        });
                });
            })
            ->when($includeStatus && filled($filters['status'] ?? null) && $filters['status'] !== 'all', fn ($query) => $query->where('status', $filters['status']))
            ->when(! $hasSearch && filled($filters['summary_year'] ?? null), fn ($query) => $query->whereYear('issued_at', $filters['summary_year']))
            ->when(! $hasSearch && filled($filters['summary_month'] ?? null), fn ($query) => $query->whereMonth('issued_at', $filters['summary_month']))
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('issued_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('issued_at', '<=', $to));
    }

    private function statuses(): array
    {
        return [
            'all' => 'Tutte',
            'draft' => 'Bozze',
            'sent' => 'Emesse',
            'paid' => 'Pagate',
            'cancelled' => 'Annullate',
        ];
    }

    private function availableYears()
    {
        return Invoice::whereHas('patient', fn ($query) => $query->where('user_id', Auth::id()))
            ->selectRaw($this->datePartExpression('year').' as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year) => (int) $year);
    }

    private function availableMonths(int $year)
    {
        return Invoice::whereHas('patient', fn ($query) => $query->where('user_id', Auth::id()))
            ->whereYear('issued_at', $year)
            ->selectRaw($this->datePartExpression('month').' as month')
            ->distinct()
            ->orderBy('month')
            ->pluck('month')
            ->map(fn ($month) => (int) $month);
    }

    private function datePartExpression(string $part): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return $part === 'year'
                ? "strftime('%Y', issued_at)"
                : "strftime('%m', issued_at)";
        }

        return $part === 'year'
            ? 'YEAR(issued_at)'
            : 'MONTH(issued_at)';
    }

    private function yearlyTotal(int $year): float
    {
        return (float) Invoice::whereHas('patient', fn ($query) => $query->where('user_id', Auth::id()))
            ->whereYear('issued_at', $year)
            ->sum('amount');
    }

    private function monthlyPaidTotal(int $year, int $month): float
    {
        return (float) Invoice::whereHas('patient', fn ($query) => $query->where('user_id', Auth::id()))
            ->where('status', 'paid')
            ->whereYear('issued_at', $year)
            ->whereMonth('issued_at', $month)
            ->sum('amount');
    }

    private function monthLabels(): array
    {
        return [
            1 => 'Gennaio',
            2 => 'Febbraio',
            3 => 'Marzo',
            4 => 'Aprile',
            5 => 'Maggio',
            6 => 'Giugno',
            7 => 'Luglio',
            8 => 'Agosto',
            9 => 'Settembre',
            10 => 'Ottobre',
            11 => 'Novembre',
            12 => 'Dicembre',
        ];
    }
}
