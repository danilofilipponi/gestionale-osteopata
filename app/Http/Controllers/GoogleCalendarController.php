<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Setting;
use App\Support\GoogleCalendarClient;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Throwable;

class GoogleCalendarController extends Controller
{
    public function connect()
    {
        abort_unless(GoogleCalendarClient::configured(), 422, 'Credenziali Google Calendar mancanti.');

        return redirect()->away(GoogleCalendarClient::authorizationUrl());
    }

    public function callback(Request $request)
    {
        if (! $request->filled('code') && ! $request->filled('error')) {
            return redirect()
                ->route('settings.agenda')
                ->with('status', 'Google Calendar e gia collegato. Puoi aprire l\'agenda o sincronizzare di nuovo gli appuntamenti.');
        }

        abort_if($request->filled('error'), 422, 'Collegamento Google Calendar annullato: '.$request->query('error'));
        abort_unless($request->query('state') === session('google_calendar_state'), 403, 'Richiesta Google Calendar non valida.');
        abort_unless($request->filled('code'), 422, 'Codice autorizzazione Google Calendar mancante.');

        GoogleCalendarClient::exchangeCode((string) $request->query('code'));
        Setting::setValue('google_calendar_enabled', '1', 'agenda');

        return redirect()
            ->route('settings.agenda')
            ->with('status', 'Google Calendar collegato correttamente.');
    }

    public function disconnect()
    {
        GoogleCalendarClient::disconnect();

        return redirect()
            ->route('settings.agenda')
            ->with('status', 'Google Calendar scollegato.');
    }

    public function sync()
    {
        $validated = request()->validate([
            'sync_year' => ['nullable', 'integer', 'min:2020', 'max:2035'],
        ]);

        $year = (int) ($validated['sync_year'] ?? now()->year);
        $from = now()->setDate($year, 1, 1)->startOfDay();
        $to = now()->setDate($year, 12, 31)->endOfDay();
        $appointments = Appointment::with('patient')
            ->whereBetween('starts_at', [$from, $to])
            ->whereNull('google_event_id')
            ->orderBy('starts_at')
            ->get();

        $exported = 0;
        $imported = 0;
        $updated = 0;
        $failed = 0;

        if (in_array(GoogleCalendarClient::syncMode(), ['write', 'two_way'], true)) {
            foreach ($appointments as $appointment) {
                try {
                    $eventId = GoogleCalendarClient::upsertAppointment($appointment);

                    if ($eventId && $appointment->google_event_id !== $eventId) {
                        $appointment->forceFill([
                            'google_event_id' => $eventId,
                            'google_calendar_id' => GoogleCalendarClient::calendarIdForType($appointment->type),
                        ])->save();
                    }

                    $exported++;
                } catch (Throwable) {
                    $failed++;
                }
            }
        }

        if (in_array(GoogleCalendarClient::syncMode(), ['read', 'two_way'], true)) {
            foreach (GoogleCalendarClient::selectedCalendarIds() as $calendarId) {
                try {
                    foreach (GoogleCalendarClient::events($from, $to, $calendarId) as $event) {
                        $result = $this->importEvent($event, $calendarId);
                        $imported += (int) ($result === 'created');
                        $updated += (int) ($result === 'updated');
                    }
                } catch (Throwable) {
                    $failed++;
                }
            }
        }

        return redirect()
            ->route('settings.agenda')
            ->with('status', "Sincronizzazione {$year} completata: {$exported} inviati a Google, {$imported} importati, {$updated} aggiornati, {$failed} non sincronizzati.");
    }

    public function refreshCalendars()
    {
        try {
            $calendars = GoogleCalendarClient::refreshCalendarList();

            return redirect()
                ->route('settings.agenda')
                ->with('status', 'Lista calendari aggiornata: '.count($calendars).' calendari trovati.');
        } catch (Throwable) {
            return redirect()
                ->route('settings.agenda')
                ->with('status', 'Non sono riuscito ad aggiornare la lista calendari. Verifica il collegamento Google Calendar.');
        }
    }

    private function importEvent(array $event, string $calendarId): ?string
    {
        if (($event['status'] ?? null) === 'cancelled' || blank($event['id'] ?? null)) {
            return null;
        }

        [$startsAt, $endsAt] = $this->eventDates($event);

        if (! $startsAt || ! $endsAt) {
            return null;
        }

        $appointment = Appointment::where('google_event_id', $event['id'])
            ->where('google_calendar_id', $calendarId)
            ->first();
        $payload = [
            'patient_id' => null,
            'title' => $event['summary'] ?? 'Evento Google Calendar',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'type' => 'personal',
            'status' => 'scheduled',
            'color' => '#64748b',
            'notes' => trim($event['description'] ?? ''),
            'google_event_id' => $event['id'],
            'google_calendar_id' => $calendarId,
        ];

        if ($appointment) {
            $appointment->update($payload);

            return 'updated';
        }

        Appointment::create($payload);

        return 'created';
    }

    private function eventDates(array $event): array
    {
        $timezone = config('app.timezone', 'Europe/Rome');
        $start = $event['start'] ?? [];
        $end = $event['end'] ?? [];

        if (isset($start['dateTime'], $end['dateTime'])) {
            return [
                Carbon::parse($start['dateTime'])->timezone($timezone),
                Carbon::parse($end['dateTime'])->timezone($timezone),
            ];
        }

        if (isset($start['date'], $end['date'])) {
            $startsAt = Carbon::parse($start['date'], $timezone)->setTime(8, 0);
            $endsAt = Carbon::parse($end['date'], $timezone)->subDay()->setTime(20, 0);

            if ($endsAt->lessThanOrEqualTo($startsAt)) {
                $endsAt = $startsAt->copy()->addHour();
            }

            return [$startsAt, $endsAt];
        }

        return [null, null];
    }
}
