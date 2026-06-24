<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Setting;
use App\Support\GoogleCalendarClient;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if ($result['reconnect_required'] ?? false) {
            return redirect()
                ->route('settings.agenda')
                ->with('status', 'Google Calendar richiede un nuovo collegamento: il token risulta scaduto o revocato. Clicca su "Collega Google Calendar" e poi rilancia la sincronizzazione.');
        }

        return redirect()
            ->route('settings.agenda')
            ->with('status', "Sincronizzazione {$year} completata: {$result['exported']} inviati a Google, {$result['imported']} importati, {$result['updated']} aggiornati, {$result['deleted']} eliminati, {$result['skipped']} gia presenti, {$result['failed']} non sincronizzati.");
    }

    public function autoSync()
    {
        try {
            $result = $this->syncCurrentYear(false);

            return response()->json(['status' => 'ok'] + $result);
        } catch (Throwable) {
            return response()->json(['status' => 'skipped']);
        }
    }

    public function syncCurrentYear(bool $pushLocalAppointments = false): array
    {
        $result = $this->syncYear(now()->year, $pushLocalAppointments);
        Setting::setValue('google_calendar_auto_synced_at', now()->toDateTimeString(), 'agenda');

        return $result;
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
        $deleted = 0;
        $failed = 0;
        $reconnectRequired = false;
        $errors = [];

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
                } catch (Throwable $exception) {
                    if (GoogleCalendarClient::authorizationFailed($exception)) {
                        GoogleCalendarClient::disconnect();
                        $reconnectRequired = true;
                        $errors[] = 'Token Google scaduto o revocato durante invio appuntamenti.';
                        break;
                    }

                    report($exception);
                    $failed++;
                }
            }
        }

        if (! $reconnectRequired && in_array(GoogleCalendarClient::syncMode(), ['read', 'two_way'], true)) {
            $selectedCalendarIds = GoogleCalendarClient::selectedCalendarIds();

            foreach ($selectedCalendarIds as $calendarId) {
                try {
                    $events = GoogleCalendarClient::events($from, $to, $calendarId);
                    $googleEventIds = collect($events)
                        ->filter(fn (array $event) => ($event['status'] ?? null) !== 'cancelled')
                        ->pluck('id')
                        ->filter()
                        ->values()
                        ->all();

                    foreach ($events as $event) {
                        $result = $this->importEvent($event, $calendarId);
                        $imported += (int) ($result === 'created');
                        $updated += (int) ($result === 'updated');
                        $skipped += (int) ($result === 'unchanged');
                    }

                    $deleted += $this->deleteMissingGoogleEvents($from, $to, $calendarId, $googleEventIds, count($selectedCalendarIds) === 1);
                } catch (Throwable $exception) {
                    if (GoogleCalendarClient::authorizationFailed($exception)) {
                        GoogleCalendarClient::disconnect();
                        $reconnectRequired = true;
                        $errors[] = 'Token Google scaduto o revocato durante lettura calendari.';
                        break;
                    }

                    report($exception);
                    $errors[] = $calendarId.': '.$exception->getMessage();
                    $failed++;
                }
            }
        }

        return compact('exported', 'imported', 'updated', 'skipped', 'deleted', 'failed')
            + [
                'reconnect_required' => $reconnectRequired,
                'errors' => $errors,
            ];
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
        $patientMatch = ($category['sync_patients'] ?? false) && $this->shouldCheckPatientMatch($startsAt)
            ? $this->matchPatient((string) ($event['summary'] ?? ''))
            : ['patient' => null, 'status' => null];

        $payload = [
            'patient_id' => $patientMatch['patient']?->id,
            'patient_match_status' => $patientMatch['status'],
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

            if ($appointment->patient_id) {
                $payload['patient_id'] = $appointment->patient_id;
                $payload['patient_match_status'] = 'matched';
            }

            if ($appointment->patient_match_status === 'ignored') {
                $payload['patient_id'] = null;
                $payload['patient_match_status'] = 'ignored';
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

    private function deleteMissingGoogleEvents(Carbon $from, Carbon $to, string $calendarId, array $googleEventIds, bool $includeLegacyWithoutCalendar): int
    {
        $query = Appointment::query()
            ->whereBetween('starts_at', [$from, $to])
            ->whereNotNull('google_event_id')
            ->where(function ($query) use ($calendarId, $includeLegacyWithoutCalendar) {
                $query->where('google_calendar_id', $calendarId);

                if ($includeLegacyWithoutCalendar) {
                    $query->orWhereNull('google_calendar_id');
                }
            });

        if ($googleEventIds !== []) {
            $query->whereNotIn('google_event_id', $googleEventIds);
        }

        $appointments = $query->get();
        $deleted = $appointments->count();

        foreach ($appointments as $appointment) {
            $appointment->delete();
        }

        return $deleted;
    }

    private function findExistingAppointment(array $payload, string $googleEventId, string $calendarId): ?Appointment
    {
        $byEventId = Appointment::where('google_event_id', $googleEventId)->get();

        if ($byEventId->count() === 1) {
            return $byEventId->first();
        }

        return $byEventId
            ->first(fn (Appointment $appointment) => in_array($appointment->google_calendar_id, [$calendarId, null], true))
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
        $calendars = collect(GoogleCalendarClient::storedCalendarList())->keyBy('id');
        $calendarColor = $calendars->get($calendarId)['backgroundColor'] ?? null;

        if ($category) {
            if (! (bool) ($category['sync_patients'] ?? false)) {
                return [
                    'key' => 'personal',
                    'color' => $calendarColor ?? '#64748b',
                    'sync_patients' => false,
                ];
            }

            return [
                'key' => $category['key'] ?? 'personal',
                'color' => $calendarColor ?? ($category['color'] ?? '#64748b'),
                'sync_patients' => (bool) ($category['sync_patients'] ?? false),
            ];
        }

        return [
            'key' => 'personal',
            'color' => $calendarColor ?? '#64748b',
            'sync_patients' => false,
        ];
    }

    private function matchPatient(string $title): array
    {
        $titleNormalized = $this->normalizePatientText($title);

        if ($titleNormalized === '') {
            return ['patient' => null, 'status' => 'pending'];
        }

        $patients = Patient::where('user_id', Auth::id())->orderBy('last_name')->orderBy('first_name')->get();
        $matches = $patients
            ->map(function (Patient $patient) use ($titleNormalized) {
                $fullName = $this->normalizePatientText($patient->list_name);
                $lastName = $this->normalizePatientText($patient->last_name);
                $firstName = $this->normalizePatientText($patient->first_name);
                $score = 0;

                if ($fullName && str_contains($titleNormalized, $fullName)) {
                    $score = 100;
                } elseif ($lastName && preg_match('/\b'.preg_quote($lastName, '/').'\b/u', $titleNormalized)) {
                    $score = $firstName && str_contains($titleNormalized, $firstName) ? 95 : 80;
                }

                return ['patient' => $patient, 'score' => $score];
            })
            ->filter(fn (array $match) => $match['score'] >= 80)
            ->sortByDesc('score')
            ->values();

        if ($matches->count() === 1 && $matches->first()['score'] >= 80) {
            return ['patient' => $matches->first()['patient'], 'status' => 'matched'];
        }

        return ['patient' => null, 'status' => 'pending'];
    }

    private function shouldCheckPatientMatch(Carbon $startsAt): bool
    {
        return $startsAt->between(
            now()->subDays(7)->startOfDay(),
            now()->addDays(7)->endOfDay()
        );
    }

    private function normalizePatientText(?string $value): string
    {
        $value = strtolower((string) $value);
        $value = preg_replace('/[^\pL\pN\s]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\b(eur|euro|x\d+|\d+)\b/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}
