<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Setting;
use App\Support\GoogleCalendarClient;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $view = in_array($request->query('view', 'week'), ['day', 'week', 'month'], true)
            ? $request->query('view', 'week')
            : 'week';
        $date = now()->parse($request->query('date', now()->toDateString()));
        $settings = $this->settings();
        $categories = $this->categories();

        [$start, $end] = match ($view) {
            'day' => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
            'month' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
            default => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
        };

        $calendarStart = $view === 'month' ? $start->copy()->startOfWeek() : $start->copy();
        $calendarEnd = $view === 'month' ? $end->copy()->endOfWeek() : $end->copy();
        $appointments = Appointment::with('patient')
            ->whereBetween('starts_at', [$calendarStart, $calendarEnd])
            ->oldest('starts_at')
            ->get();
        $patientMatchStart = now()->subDays(7)->startOfDay();
        $patientMatchEnd = now()->addDays(7)->endOfDay();
        $pendingPatientMatches = Appointment::whereNull('patient_id')
            ->where('patient_match_status', 'pending')
            ->whereNotNull('google_event_id')
            ->whereBetween('starts_at', [$patientMatchStart, $patientMatchEnd])
            ->oldest('starts_at')
            ->limit(12)
            ->get()
            ->map(fn (Appointment $appointment) => [
                'appointment' => $appointment,
                'suggestions' => $this->patientSuggestions($appointment->title),
            ]);

        return view('appointments.index', [
            'appointments' => $appointments,
            'patients' => Patient::orderBy('last_name')->orderBy('first_name')->get(),
            'view' => $view,
            'date' => $date,
            'start' => $start,
            'end' => $end,
            'calendarStart' => $calendarStart,
            'calendarEnd' => $calendarEnd,
            'calendarDays' => collect(CarbonPeriod::create($calendarStart, $calendarEnd)),
            'timeSlots' => $this->timeSlots($settings['agenda_start_time'], $settings['agenda_end_time'], 15),
            'settings' => $settings,
            'categories' => $categories,
            'appointmentsByDate' => $appointments->groupBy(fn (Appointment $appointment) => $appointment->starts_at->toDateString()),
            'statusLabels' => $this->statusLabels(),
            'pendingPatientMatches' => $pendingPatientMatches,
            'showPatientMatchModal' => (bool) $request->session()->pull('show_patient_match_modal', false),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedAppointment($request);
        $data['color'] = ($data['color'] ?? null) ?: $this->categoryColor($data['type']);

        $appointment = Appointment::create($data);
        $this->syncWithGoogle($appointment);

        return back()->with('status', 'Appuntamento creato.');
    }

    public function update(Request $request, Appointment $appointment)
    {
        $data = $this->validatedAppointment($request);
        $data['color'] = ($data['color'] ?? null) ?: $this->categoryColor($data['type']);

        $appointment->update($data);
        $this->syncWithGoogle($appointment);

        return back()->with('status', 'Appuntamento aggiornato.');
    }

    public function move(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $appointment->update($data);
        $this->syncWithGoogle($appointment);

        return response()->json([
            'status' => 'ok',
            'message' => 'Appuntamento spostato.',
        ]);
    }

    public function destroy(Appointment $appointment)
    {
        $this->deleteFromGoogle($appointment);
        $appointment->delete();

        return back()->with('status', 'Appuntamento eliminato.');
    }

    public function resolvePatient(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
        ]);

        $appointment->update([
            'patient_id' => $data['patient_id'],
            'patient_match_status' => 'matched',
            'title' => Patient::find($data['patient_id'])->list_name,
        ]);
        $this->syncWithGoogle($appointment);

        return back()->with('status', 'Appuntamento abbinato al paziente.');
    }

    public function ignorePatientMatch(Appointment $appointment)
    {
        $appointment->update([
            'patient_match_status' => 'ignored',
        ]);

        return back()->with('status', 'Appuntamento lasciato senza abbinamento paziente.');
    }

    public function createPatientFromMatch(Appointment $appointment)
    {
        $appointment->update([
            'patient_match_status' => 'ignored',
        ]);

        $nameParts = preg_split('/\s+/', trim($appointment->title), 2) ?: [];

        return redirect()
            ->route('patients.create', [
                'last_name' => $nameParts[0] ?? '',
                'first_name' => $nameParts[1] ?? '',
                'appointment_id' => $appointment->id,
            ])
            ->with('status', 'Ricerca paziente disattivata per questo appuntamento. Puoi creare una nuova scheda paziente.');
    }

    private function validatedAppointment(Request $request): array
    {
        return $request->validate([
            'patient_id' => ['nullable', 'exists:patients,id'],
            'title' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'type' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:scheduled,confirmed,completed,cancelled,no_show'],
            'color' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function settings(): array
    {
        return [
            'agenda_start_time' => Setting::getValue('agenda_start_time', '08:00'),
            'agenda_end_time' => Setting::getValue('agenda_end_time', '20:00'),
            'agenda_slot_minutes' => Setting::getValue('agenda_slot_minutes', '30'),
            'agenda_default_duration' => Setting::getValue('agenda_default_duration', '60'),
            'google_calendar_enabled' => Setting::getValue('google_calendar_enabled', '0'),
            'google_calendar_id' => Setting::getValue('google_calendar_id', ''),
        ];
    }

    private function categories(): array
    {
        $categories = json_decode(Setting::getValue('agenda_categories', '[]'), true) ?: [];

        if ($categories !== []) {
            return $this->ensurePersonalCategory($categories);
        }

        return [
            ['key' => 'visit', 'label' => 'Visita osteopatica', 'color' => '#5f948a'],
            ['key' => 'personal', 'label' => 'Impegno personale', 'color' => '#64748b'],
            ['key' => 'holiday', 'label' => 'Ferie', 'color' => '#d97706'],
            ['key' => 'absence', 'label' => 'Assenza', 'color' => '#dc2626'],
        ];
    }

    private function ensurePersonalCategory(array $categories): array
    {
        $hasPersonal = collect($categories)->contains(fn (array $category) => ($category['key'] ?? null) === 'personal');

        if (! $hasPersonal) {
            $categories[] = [
                'key' => 'personal',
                'label' => 'Personale',
                'color' => '#64748b',
                'google_calendar_id' => '',
                'sync_patients' => false,
            ];
        }

        return array_values($categories);
    }

    private function categoryColor(string $type): string
    {
        $calendarId = GoogleCalendarClient::calendarIdForType($type);
        $calendar = collect(GoogleCalendarClient::storedCalendarList())->firstWhere('id', $calendarId);

        if (! empty($calendar['backgroundColor'])) {
            return $calendar['backgroundColor'];
        }

        $category = collect($this->categories())->firstWhere('key', $type);

        return in_array($type, ['visit', 'visita'], true) ? '#039be5' : ($category['color'] ?? '#039be5');
    }

    private function timeSlots(string $start, string $end, int $minutes): array
    {
        $slots = [];
        $cursor = now()->setTimeFromTimeString($start);
        $limit = now()->setTimeFromTimeString($end);

        while ($cursor < $limit) {
            $slots[] = $cursor->format('H:i');
            $cursor->addMinutes($minutes);
        }

        return $slots;
    }

    private function statusLabels(): array
    {
        return [
            'scheduled' => 'Programmato',
            'confirmed' => 'Confermato',
            'completed' => 'Svolto',
            'cancelled' => 'Annullato',
            'no_show' => 'Non presentato',
        ];
    }

    private function patientSuggestions(string $title)
    {
        $titleNormalized = $this->normalizePatientText($title);

        return Patient::where('user_id', Auth::id())
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function (Patient $patient) use ($titleNormalized) {
                $fullName = $this->normalizePatientText($patient->list_name);
                $lastName = $this->normalizePatientText($patient->last_name);
                $firstName = $this->normalizePatientText($patient->first_name);
                $score = 0;

                if ($fullName && str_contains($titleNormalized, $fullName)) {
                    $score = 100;
                } elseif ($lastName && str_contains($titleNormalized, $lastName)) {
                    $score = $firstName && str_contains($titleNormalized, $firstName) ? 95 : 80;
                } elseif ($firstName && str_contains($titleNormalized, $firstName)) {
                    $score = 55;
                }

                return ['patient' => $patient, 'score' => $score];
            })
            ->filter(fn (array $suggestion) => $suggestion['score'] >= 55)
            ->sortByDesc('score')
            ->take(5)
            ->values();
    }

    private function normalizePatientText(?string $value): string
    {
        $value = strtolower((string) $value);
        $value = preg_replace('/[^\pL\pN\s]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\b(eur|euro|x\d+|\d+)\b/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function syncWithGoogle(Appointment $appointment): void
    {
        try {
            $eventId = GoogleCalendarClient::upsertAppointment($appointment->loadMissing('patient'));

            if ($eventId && $appointment->google_event_id !== $eventId) {
                $appointment->forceFill([
                    'google_event_id' => $eventId,
                    'google_calendar_id' => GoogleCalendarClient::calendarIdForType($appointment->type),
                ])->save();
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function deleteFromGoogle(Appointment $appointment): void
    {
        try {
            GoogleCalendarClient::deleteAppointment($appointment);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
