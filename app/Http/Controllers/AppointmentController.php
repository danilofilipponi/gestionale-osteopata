<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $view = $request->query('view', 'week');
        $date = now()->parse($request->query('date', now()->toDateString()));

        [$start, $end] = match ($view) {
            'day' => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
            'month' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
            default => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
        };

        return view('appointments.index', [
            'appointments' => Appointment::with('patient')
                ->whereBetween('starts_at', [$start, $end])
                ->oldest('starts_at')
                ->get(),
            'patients' => Patient::orderBy('last_name')->orderBy('first_name')->get(),
            'view' => $view,
            'date' => $date,
            'start' => $start,
            'end' => $end,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedAppointment($request);
        $this->preventOverlap($data['starts_at'], $data['ends_at']);

        Appointment::create($data);

        return back()->with('status', 'Appuntamento creato.');
    }

    public function update(Request $request, Appointment $appointment)
    {
        $data = $this->validatedAppointment($request);
        $this->preventOverlap($data['starts_at'], $data['ends_at'], $appointment);

        $appointment->update($data);

        return back()->with('status', 'Appuntamento aggiornato.');
    }

    public function destroy(Appointment $appointment)
    {
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
            'type' => ['required', Rule::in(['visit', 'personal', 'holiday', 'absence'])],
            'status' => ['required', Rule::in(['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'])],
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
            ->exists();

        abort_if($overlap, 422, 'Esiste gia un appuntamento nello stesso orario.');
    }
}
