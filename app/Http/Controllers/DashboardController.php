<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\TreatmentSession;
use App\Support\TreatmentSessionDefaults;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $userId = Auth::id();
        $monthStart = now()->copy()->startOfMonth();
        $monthEnd = now()->copy()->endOfMonth();
        $yearStart = now()->copy()->startOfYear();
        $yearEnd = now()->copy()->endOfYear();
        $monthlyRevenue = Invoice::whereHas('patient', fn ($query) => $query->where('user_id', $userId))
            ->whereBetween('issued_at', [$monthStart, $monthEnd])
            ->sum('amount');
        $paidInvoicesThisMonth = Invoice::whereHas('patient', fn ($query) => $query->where('user_id', $userId))
            ->where('status', 'paid')
            ->whereBetween('issued_at', [$monthStart, $monthEnd])
            ->sum('amount');
        $patientSyncCategories = collect(json_decode(Setting::getValue('agenda_categories', '[]'), true) ?: [])
            ->filter(fn (array $category) => (bool) ($category['sync_patients'] ?? false))
            ->values();
        $defaultSessionRate = TreatmentSessionDefaults::defaultRate();
        $monthlyActiveRevenue = Invoice::whereHas('patient', fn ($query) => $query->where('user_id', $userId))
            ->where('status', '!=', 'cancelled')
            ->whereBetween('issued_at', [$yearStart, $yearEnd])
            ->get()
            ->groupBy(fn (Invoice $invoice) => (int) $invoice->issued_at->format('n'));
        $monthLabels = [
            1 => 'Gen', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mag', 6 => 'Giu',
            7 => 'Lug', 8 => 'Ago', 9 => 'Set', 10 => 'Ott', 11 => 'Nov', 12 => 'Dic',
        ];
        $activeRevenueChart = collect(range(1, 12))->map(function (int $month) use ($monthlyActiveRevenue, $monthLabels) {
            return [
                'month' => $monthLabels[$month],
                'total' => (float) ($monthlyActiveRevenue->get($month)?->sum('amount') ?? 0),
            ];
        });
        $maxActiveRevenue = max(1, $activeRevenueChart->max('total'));

        $todayAppointments = Appointment::with('patient')
            ->whereDate('starts_at', now()->toDateString())
            ->oldest('starts_at')
            ->get();
        $patientSyncAppointments = $todayAppointments
            ->filter(fn (Appointment $appointment) => ! in_array($appointment->status, ['cancelled', 'no_show'], true))
            ->filter(fn (Appointment $appointment) => $patientSyncCategories->contains(function (array $category) use ($appointment) {
                $categoryCalendarId = $category['google_calendar_id'] ?? null;

                if (($category['key'] ?? null) !== $appointment->type) {
                    return false;
                }

                if (filled($categoryCalendarId) && filled($appointment->google_calendar_id)) {
                    return $categoryCalendarId === $appointment->google_calendar_id;
                }

                return blank($categoryCalendarId);
            }));
        $expectedDailyAppointments = $patientSyncAppointments->count();

        return view('dashboard', [
            'patientsCount' => Patient::where('user_id', $userId)->count(),
            'todayAppointments' => $todayAppointments,
            'patientSyncAppointmentsCount' => $patientSyncAppointments->count(),
            'expectedDailyIncome' => $expectedDailyAppointments * (float) ($defaultSessionRate['amount'] ?? 0),
            'sessionsThisMonth' => TreatmentSession::whereHas('patient', fn ($query) => $query->where('user_id', $userId))
                ->whereBetween('session_date', [$monthStart, $monthEnd])
                ->count(),
            'newPatientsThisMonth' => Patient::where('user_id', $userId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count(),
            'monthlyRevenue' => $monthlyRevenue,
            'paidInvoicesThisMonth' => $paidInvoicesThisMonth,
            'openInvoicesTotal' => Invoice::whereHas('patient', fn ($query) => $query->where('user_id', $userId))
                ->whereIn('status', ['draft', 'sent'])
                ->sum('amount'),
            'recentPatients' => Patient::where('user_id', $userId)
                ->latest()
                ->take(5)
                ->get(),
            'upcomingSessions' => TreatmentSession::with('patient')
                ->whereHas('patient', fn ($query) => $query->where('user_id', $userId))
                ->whereDate('session_date', '>=', now()->toDateString())
                ->oldest('session_date')
                ->take(5)
                ->get(),
            'openInvoices' => Invoice::with('patient')
                ->whereHas('patient', fn ($query) => $query->where('user_id', $userId))
                ->whereIn('status', ['draft', 'sent'])
                ->oldest('issued_at')
                ->take(5)
                ->get(),
            'activeRevenueChart' => $activeRevenueChart,
            'maxActiveRevenue' => $maxActiveRevenue,
            'activeRevenueYearTotal' => $activeRevenueChart->sum('total'),
        ]);
    }
}
