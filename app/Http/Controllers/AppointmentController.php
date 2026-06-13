<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Setting;
use App\Support\GoogleCalendarClient;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedAppointment($request);
        $data['color'] = $data['color'] ?: $this->categoryColor($data['type']);
        $this->preventOverlap($data['starts_at'], $data['ends_at']);

        $appointment = Appointment::create($data);
        $this->syncWithGoogle($appointment);

        return back()->with('status', 'Appuntamento creato.');
    }

    public function update(Request $request, Appointment $appointment)
    {
        $data = $this->validatedAppointment($request);
        $data['color'] = $data['color'] ?: $this->categoryColor($data['type']);
        $this->preventOverlap($data['starts_at'], $data['ends_at'], $appointment);

        $appointment->update($data);
        $this->syncWithGoogle($appointment);

        return back()->with('status', 'Appuntamento aggiornato.');
    }

    public function destroy(Appointment $appointment)
    {
        $this->deleteFromGoogle($appointment);
        $appointment->delete();

        return back()->with('status', 'Appuntamento eliminato.');
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

    private function preventOverlap(string $startsAt, string $endsAt, ?Appointment $current = null): void
    {
        $overlap = Appointment::query()
            ->when($current, fn ($query) => $query->whereKeyNot($current->id))
            ->where('status', '!=', 'cancelled')
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->oldest('starts_at')
            ->first();

        if ($overlap) {
            throw ValidationException::withMessages([
                'starts_at' => sprintf(
                    'Esiste gia un appuntamento nello stesso orario: %s, dalle %s alle %s.',
                    $overlap->title,
                    $overlap->starts_at->format('H:i'),
                    $overlap->ends_at->format('H:i')
                ),
            ]);
        }
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
            return $categories;
        }

        return [
            ['key' => 'visit', 'label' => 'Visita osteopatica', 'color' => '#5f948a'],
            ['key' => 'personal', 'label' => 'Impegno personale', 'color' => '#64748b'],
            ['key' => 'holiday', 'label' => 'Ferie', 'color' => '#d97706'],
            ['key' => 'absence', 'label' => 'Assenza', 'color' => '#dc2626'],
        ];
    }

    private function categoryColor(string $type): string
    {
        $category = collect($this->categories())->firstWhere('key', $type);

        return $category['color'] ?? '#5f948a';
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
