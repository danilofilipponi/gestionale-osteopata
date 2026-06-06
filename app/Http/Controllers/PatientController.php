<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $patients = Patient::where('user_id', Auth::id())
            ->when($request->search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(12)
            ->withQueryString();

        return view('patients.index', compact('patients'));
    }

    public function create()
    {
        return view('patients.create');
    }

    public function store(Request $request)
    {
        $patient = Patient::create($this->validatedPatient($request) + [
            'user_id' => Auth::id(),
        ]);

        return redirect()
            ->route('patients.show', $patient)
            ->with('status', 'Paziente creato correttamente.');
    }

    public function show(Patient $patient)
    {
        $this->authorizePatient($patient);

        $patient->load(['medicalRecord', 'treatmentSessions', 'invoices']);

        return view('patients.show', compact('patient'));
    }

    public function storeMedicalRecord(Request $request, Patient $patient)
    {
        $this->authorizePatient($patient);

        $patient->medicalRecord()->updateOrCreate([], $request->validate([
            'reason_for_visit' => ['nullable', 'string'],
            'anamnesis' => ['nullable', 'string'],
            'diagnostic_notes' => ['nullable', 'string'],
            'treatment_plan' => ['nullable', 'string'],
            'contraindications' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Cartella clinica aggiornata.');
    }

    public function storeTreatmentSession(Request $request, Patient $patient)
    {
        $this->authorizePatient($patient);

        $patient->treatmentSessions()->create($request->validate([
            'session_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'outcome' => ['nullable', 'string'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'paid' => ['nullable', 'boolean'],
        ]));

        return back()->with('status', 'Seduta registrata.');
    }

    public function storeInvoice(Request $request, Patient $patient)
    {
        $this->authorizePatient($patient);

        $patient->invoices()->create($request->validate([
            'number' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,sent,paid,cancelled'],
            'description' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Fattura registrata.');
    }

    private function validatedPatient(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'fiscal_code' => ['nullable', 'string', 'max:16'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function authorizePatient(Patient $patient): void
    {
        abort_unless($patient->user_id === Auth::id(), 404);
    }
}
