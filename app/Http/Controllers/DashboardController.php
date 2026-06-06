<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\TreatmentSession;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $userId = Auth::id();

        return view('dashboard', [
            'patientsCount' => Patient::where('user_id', $userId)->count(),
            'todayAppointments' => Appointment::with('patient')
                ->whereDate('starts_at', now()->toDateString())
                ->oldest('starts_at')
                ->get(),
            'sessionsThisMonth' => TreatmentSession::whereHas('patient', fn ($query) => $query->where('user_id', $userId))
                ->whereBetween('session_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'newPatientsThisMonth' => Patient::where('user_id', $userId)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'paidInvoicesThisMonth' => Invoice::whereHas('patient', fn ($query) => $query->where('user_id', $userId))
                ->where('status', 'paid')
                ->whereBetween('issued_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
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
        ]);
    }
}
