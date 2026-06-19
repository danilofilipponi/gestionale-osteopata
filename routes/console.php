<?php

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\TreatmentSession;
use App\Support\GoogleCalendarClient;
use App\Support\TreatmentSessionAutomation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('google-calendar:clear-event-colors {--type=*}', function () {
    $types = $this->option('type');
    $calendars = collect(GoogleCalendarClient::storedCalendarList())->keyBy('id');

    $query = Appointment::query()
        ->whereNotNull('google_event_id')
        ->when($types !== [], fn ($query) => $query->whereIn('type', $types));

    $total = $query->count();
    $corrected = 0;
    $localUpdated = 0;
    $failed = 0;
    $firstError = null;

    $this->info("Eventi Google da correggere: {$total}");

    $query->chunkById(50, function ($appointments) use ($calendars, &$corrected, &$localUpdated, &$failed, &$firstError) {
        foreach ($appointments as $appointment) {
            try {
                $calendarColor = $calendars->get($appointment->google_calendar_id)['backgroundColor'] ?? null;

                if ($calendarColor && $appointment->color !== $calendarColor) {
                    $appointment->forceFill(['color' => $calendarColor])->save();
                    $localUpdated++;
                }

                GoogleCalendarClient::clearAppointmentColor($appointment);
                $corrected++;
            } catch (Throwable $exception) {
                $failed++;
                $firstError ??= $exception->getMessage();
                $this->warn("Non corretto: {$appointment->title} ({$appointment->starts_at->format('d/m/Y H:i')})");
            }
        }
    });

    $this->info("Correzione completata: {$corrected} corretti su Google, {$localUpdated} colori locali aggiornati, {$failed} non corretti.");

    if ($firstError) {
        $this->warn("Primo errore: {$firstError}");
    }
})->purpose('Rimuove il colore forzato dagli eventi Google Calendar gia sincronizzati.');

Artisan::command('treatment-sessions:auto-create', function () {
    $created = TreatmentSessionAutomation::createDueFromAgenda();

    $this->info("Sedute automatiche create: {$created}");
})->purpose('Registra automaticamente le sedute dagli appuntamenti in agenda.');

Artisan::command('treatment-sessions:cleanup-automatic', function () {
    $automaticSessions = TreatmentSession::query()
        ->where(function ($query) {
            $query->where('treatment', 'like', '%automaticamente%')
                ->orWhere('notes', 'like', '%Generata 10 minuti%')
                ->orWhere('notes', 'like', '%Prima seduta registrata%');
        })
        ->oldest('id')
        ->get();

    $cleared = 0;
    $deleted = 0;

    $automaticSessions
        ->groupBy(fn (TreatmentSession $session) => implode('|', [
            $session->appointment_id ?: 'no-appointment',
            $session->invoice_id ?: 'no-invoice',
            $session->patient_id,
            $session->session_date?->toDateString(),
            $session->title,
            $session->fee,
        ]))
        ->each(function ($sessions) use (&$deleted) {
            $sessions->skip(1)->each(function (TreatmentSession $session) use (&$deleted) {
                $session->delete();
                $deleted++;
            });
        });

    TreatmentSession::query()
        ->whereNull('appointment_id')
        ->whereNull('invoice_id')
        ->oldest('id')
        ->get()
        ->each(function (TreatmentSession $session) use (&$deleted) {
            $linkedDuplicate = TreatmentSession::query()
                ->where('id', '!=', $session->id)
                ->where('patient_id', $session->patient_id)
                ->whereDate('session_date', $session->session_date)
                ->where('title', $session->title)
                ->where('fee', $session->fee)
                ->where(function ($query) {
                    $query->whereNotNull('appointment_id')
                        ->orWhereNotNull('invoice_id');
                })
                ->exists();

            if ($linkedDuplicate) {
                $session->delete();
                $deleted++;
            }
        });

    $automaticSessions = TreatmentSession::query()
        ->where(function ($query) {
            $query->where('treatment', 'like', '%automaticamente%')
                ->orWhere('notes', 'like', '%Generata 10 minuti%')
                ->orWhere('notes', 'like', '%Prima seduta registrata%');
        })
        ->get();

    foreach ($automaticSessions as $session) {
        $session->update([
            'treatment' => null,
            'notes' => null,
        ]);
        $cleared++;
    }

    $this->info("Pulizia completata: {$deleted} doppioni eliminati, {$cleared} sedute svuotate nei campi trattamento/note.");
})->purpose('Pulisce doppioni e testi delle sedute generate automaticamente.');

Artisan::command('treatment-sessions:link-invoices', function () {
    $linked = 0;

    TreatmentSession::query()
        ->whereNull('invoice_id')
        ->oldest('session_date')
        ->get()
        ->each(function (TreatmentSession $session) use (&$linked) {
            $invoice = Invoice::query()
                ->where('patient_id', $session->patient_id)
                ->whereDate('issued_at', $session->session_date)
                ->whereDoesntHave('patient.treatmentSessions', fn ($query) => $query->whereColumn('invoice_id', 'invoices.id'))
                ->oldest('id')
                ->first();

            if (! $invoice) {
                return;
            }

            $session->update([
                'invoice_id' => $invoice->id,
                'paid' => $invoice->status === 'paid',
            ]);
            $linked++;
        });

    $this->info("Sedute collegate a fatture: {$linked}");
})->purpose('Collega sedute esistenti alle fatture dello stesso giorno.');

Schedule::command('treatment-sessions:auto-create')->everyMinute()->withoutOverlapping();
