<?php

use App\Models\Appointment;
use App\Support\GoogleCalendarClient;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
