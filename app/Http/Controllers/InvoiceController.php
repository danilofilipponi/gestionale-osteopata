<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\InvoiceXmlExporter;
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
            'quick_filter' => ['nullable', 'in:today,last_7_days,not_sent'],
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

        $exportRange = $this->exportRange(clone $query);

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
            'filters' => [
                'search' => $validated['search'] ?? '',
                'status' => $validated['status'] ?? 'all',
                'quick_filter' => $validated['quick_filter'] ?? '',
                'from' => $validated['from'] ?? '',
                'to' => $validated['to'] ?? '',
                'summary_year' => $selectedYear,
                'summary_month' => $selectedMonth,
            ],
            'statuses' => $this->statuses(),
            'quickFilters' => $this->quickFilters(),
            'exportRange' => $exportRange,
            'availableYears' => $availableYears,
            'availableMonths' => $availableMonths,
            'monthLabels' => $this->monthLabels(),
        ]);
    }

    public function exportXml(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:all,draft,sent,paid,cancelled'],
            'quick_filter' => ['nullable', 'in:today,last_7_days,not_sent'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'summary_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'summary_month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $validated['summary_year'] = (int) ($validated['summary_year'] ?? now()->year);
        $validated['summary_month'] = (int) ($validated['summary_month'] ?? now()->month);

        $invoices = $this->invoiceQuery($validated)
            ->with('patient')
            ->orderBy('issued_at')
            ->orderBy('number')
            ->get();

        abort_if($invoices->isEmpty(), 422, 'Nessuna fattura da esportare.');

        $from = $invoices->min('issued_at')?->format('Y-m-d') ?: 'inizio';
        $to = $invoices->max('issued_at')?->format('Y-m-d') ?: 'fine';

        $invoices->each->update(['xml_downloaded_at' => now()]);

        return response(InvoiceXmlExporter::make($invoices), 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="export-fatture-xml-'.$from.'-'.$to.'.zip"',
            'Cache-Control' => 'no-store, no-cache',
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
            ->when(($filters['quick_filter'] ?? null) === 'today', fn ($query) => $query->whereDate('issued_at', now()->toDateString()))
            ->when(($filters['quick_filter'] ?? null) === 'last_7_days', fn ($query) => $query
                ->whereDate('issued_at', '>=', now()->copy()->subDays(6)->toDateString())
                ->whereDate('issued_at', '<=', now()->toDateString()))
            ->when(($filters['quick_filter'] ?? null) === 'not_sent', fn ($query) => $query->whereNull('xml_downloaded_at'))
            ->when(! $hasSearch && filled($filters['summary_year'] ?? null), fn ($query) => $query->whereYear('issued_at', $filters['summary_year']))
            ->when(! $hasSearch && filled($filters['summary_month'] ?? null), fn ($query) => $query->whereMonth('issued_at', $filters['summary_month']))
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('issued_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('issued_at', '<=', $to));
    }

    private function quickFilters(): array
    {
        return [
            'last_7_days' => 'Ultimi 7 gg',
            'today' => 'Oggi',
            'not_sent' => 'Non inviate',
        ];
    }

    private function exportRange($query): array
    {
        $dates = $query
            ->selectRaw('MIN(issued_at) as first_date, MAX(issued_at) as last_date')
            ->first();

        return [
            'from' => $dates?->first_date ? date('d/m/Y', strtotime($dates->first_date)) : '',
            'to' => $dates?->last_date ? date('d/m/Y', strtotime($dates->last_date)) : '',
        ];
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
