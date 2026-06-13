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
        $result = $this->syncYear($year, true);

        return redirect()
            ->route('settings.agenda')
            ->with('status', "Sincronizzazione {$year} completata: {$result['exported']} inviati a Google, {$result['imported']} importati, {$result['updated']} aggiornati, {$result['skipped']} gia presenti, {$result['failed']} non sincronizzati.");
    }

    public function autoSync()
    {
        $lastSync = Setting::getValue('google_calendar_auto_synced_at');

        if ($lastSync && now()->diffInMinutes(Carbon::parse($lastSync)) < 30) {
            return response()->json(['status' => 'skipped']);
        }

        $result = $this->syncYear(now()->year, false);
        Setting::setValue('google_calendar_auto_synced_at', now()->toDateTimeString(), 'agenda');

        return response()->json(['status' => 'ok'] + $result);
    }

    private function syncYear(int $year, bool $pushLocalAppointments): array
    {
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
        $skipped = 0;
        $failed = 0;

        if ($pushLocalAppointments && in_array(GoogleCalendarClient::syncMode(), ['write', 'two_way'], true)) {
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
                        $skipped += (int) ($result === 'unchanged');
                    }
                } catch (Throwable) {
                    $failed++;
                }
            }
        }

        return compact('exported', 'imported', 'updated', 'skipped', 'failed');
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

        $category = $this->categoryForCalendar($calendarId);
        $payload = [
            'patient_id' => null,
            'title' => $event['summary'] ?? 'Evento Google Calendar',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'type' => $category['key'],
            'status' => 'scheduled',
            'color' => $category['color'],
            'notes' => trim($event['description'] ?? ''),
            'google_event_id' => $event['id'],
            'google_calendar_id' => $calendarId,
        ];
        $appointment = $this->findExistingAppointment($payload, $event['id'], $calendarId);

        if ($appointment) {
            if ($appointment->google_event_id && $appointment->google_event_id !== $event['id']) {
                return 'unchanged';
            }

            if ($this->appointmentAlreadyMatches($appointment, $payload)) {
                return 'unchanged';
            }

            $appointment->update($payload);

            return 'updated';
        }

        Appointment::create($payload);

        return 'created';
    }

    private function findExistingAppointment(array $payload, string $googleEventId, string $calendarId): ?Appointment
    {
        return Appointment::where('google_event_id', $googleEventId)
            ->where(function ($query) use ($calendarId) {
                $query->where('google_calendar_id', $calendarId)
                    ->orWhereNull('google_calendar_id');
            })
            ->first()
            ?: Appointment::where('title', $payload['title'])
                ->where('starts_at', $payload['starts_at'])
                ->where('ends_at', $payload['ends_at'])
                ->first();
    }

    private function appointmentAlreadyMatches(Appointment $appointment, array $payload): bool
    {
        foreach ($payload as $key => $value) {
            $current = $appointment->{$key};

            if ($current instanceof Carbon) {
                $current = $current->toDateTimeString();
                $value = $value instanceof Carbon ? $value->toDateTimeString() : Carbon::parse($value)->toDateTimeString();
            }

            if ((string) $current !== (string) $value) {
                return false;
            }
        }

        return true;
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

    private function categoryForCalendar(string $calendarId): array
    {
        $categories = json_decode(Setting::getValue('agenda_categories', '[]'), true) ?: [];
        $category = collect($categories)->firstWhere('google_calendar_id', $calendarId);

        if ($category) {
            return [
                'key' => $category['key'] ?? 'personal',
                'color' => $category['color'] ?? '#64748b',
            ];
        }

        $calendars = collect(GoogleCalendarClient::storedCalendarList())->keyBy('id');

        return [
            'key' => 'personal',
            'color' => $calendars->get($calendarId)['backgroundColor'] ?? '#64748b',
        ];
    }
}
