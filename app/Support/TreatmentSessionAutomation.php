<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\TreatmentSession;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class TreatmentSessionAutomation
{
    public static function createDueFromAgenda(?Carbon $now = null): int
    {
        $now ??= now();
        $windowEnd = $now->copy()->addMinutes(10);
        $created = 0;

        Appointment::with('patient.treatmentSessions')
            ->whereNotNull('patient_id')
            ->whereBetween('ends_at', [$now, $windowEnd])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->oldest('ends_at')
            ->get()
            ->each(function (Appointment $appointment) use (&$created) {
                try {
                    $wasCreated = DB::transaction(function () use ($appointment) {
                        $lockedAppointment = Appointment::with('patient')
                            ->whereKey($appointment->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $lockedAppointment || ! $lockedAppointment->patient || in_array($lockedAppointment->status, ['cancelled', 'no_show'], true)) {
                            return false;
                        }

                        if (TreatmentSession::where('appointment_id', $lockedAppointment->id)->exists()) {
                            return false;
                        }

                        $rate = self::rateForPatient($lockedAppointment->patient);

                        $lockedAppointment->patient->treatmentSessions()->create([
                            'appointment_id' => $lockedAppointment->id,
                            'session_date' => $lockedAppointment->starts_at->toDateString(),
                            'title' => $rate['name'],
                            'treatment' => null,
                            'pain_level' => null,
                            'notes' => null,
                            'fee' => $rate['amount'],
                            'paid' => false,
                        ]);

                        return true;
                    });

                    if ($wasCreated) {
                        $created++;
                    }
                } catch (QueryException) {
                    return;
                }
            });

        return $created;
    }

    public static function registerInvoice(Patient $patient, Invoice $invoice): bool
    {
        try {
            return DB::transaction(function () use ($patient, $invoice) {
                $lockedPatient = Patient::whereKey($patient->id)->lockForUpdate()->first();

                if (! $lockedPatient) {
                    return false;
                }

                if (TreatmentSession::where('invoice_id', $invoice->id)->exists()) {
                    return false;
                }

                $existingSession = $lockedPatient->treatmentSessions()
                    ->whereDate('session_date', $invoice->issued_at)
                    ->whereNull('invoice_id')
                    ->orderByDesc('appointment_id')
                    ->orderByDesc('id')
                    ->first();

                if ($existingSession) {
                    $existingSession->update([
                        'invoice_id' => $invoice->id,
                        'paid' => $invoice->status === 'paid',
                    ]);

                    return true;
                }

                if ($lockedPatient->treatmentSessions()->exists()) {
                    return false;
                }

                $lockedPatient->treatmentSessions()->create([
                    'invoice_id' => $invoice->id,
                    'session_date' => $invoice->issued_at->toDateString(),
                    'title' => $invoice->service ?: self::rateForPatient($lockedPatient)['name'],
                    'treatment' => null,
                    'pain_level' => null,
                    'notes' => null,
                    'fee' => self::invoiceSessionFee($invoice),
                    'paid' => $invoice->status === 'paid',
                ]);

                return true;
            });
        } catch (QueryException) {
            return false;
        }
    }

    private static function rateForPatient(Patient $patient): array
    {
        $lastSession = $patient->treatmentSessions()
            ->whereNotNull('fee')
            ->orderByDesc('session_date')
            ->orderByDesc('id')
            ->first();

        if ($lastSession) {
            return [
                'name' => $lastSession->title ?: TreatmentSessionDefaults::defaultRate()['name'],
                'amount' => (float) $lastSession->fee,
            ];
        }

        $default = TreatmentSessionDefaults::defaultRate();

        return [
            'name' => $default['name'] ?? 'Seduta osteopatica',
            'amount' => (float) ($default['amount'] ?? 0),
        ];
    }

    private static function invoiceSessionFee(Invoice $invoice): float
    {
        $amounts = InvoiceDefaults::amounts($invoice);

        return (float) ($amounts['line'] ?? $invoice->line_amount ?? $invoice->amount ?? 0);
    }
}
