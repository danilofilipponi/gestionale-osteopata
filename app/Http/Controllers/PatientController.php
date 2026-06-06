<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\TreatmentSession;
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

        $patient->load(['medicalRecord', 'treatmentSessions', 'invoices', 'privacyConsent']);

        return view('patients.show', compact('patient'));
    }

    public function edit(Patient $patient)
    {
        $this->authorizePatient($patient);

        return view('patients.edit', compact('patient'));
    }

    public function update(Request $request, Patient $patient)
    {
        $this->authorizePatient($patient);

        $patient->update($this->validatedPatient($request));

        return redirect()
            ->route('patients.show', $patient)
            ->with('status', 'Paziente aggiornato correttamente.');
    }

    public function destroy(Patient $patient)
    {
        $this->authorizePatient($patient);

        $patient->delete();

        return redirect()
            ->route('patients.index')
            ->with('status', 'Paziente eliminato.');
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

    public function storePrivacyConsent(Request $request, Patient $patient)
    {
        $this->authorizePatient($patient);

        $validated = $request->validate([
            'signed_at' => ['nullable', 'date'],
            'signature_method' => ['nullable', 'string', 'max:255'],
            'document_version' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $patient->privacyConsent()->updateOrCreate([], $validated + [
            'privacy_policy_accepted' => $request->boolean('privacy_policy_accepted'),
            'health_data_processing_accepted' => $request->boolean('health_data_processing_accepted'),
            'marketing_accepted' => $request->boolean('marketing_accepted'),
        ]);

        return back()->with('status', 'Consenso privacy aggiornato.');
    }

    public function storeTreatmentSession(Request $request, Patient $patient)
    {
        $this->authorizePatient($patient);

        $data = $this->validatedTreatmentSession($request);
        $data['paid'] = $request->boolean('paid');

        $patient->treatmentSessions()->create($data);

        return back()->with('status', 'Seduta registrata.');
    }

    public function updateTreatmentSession(Request $request, Patient $patient, TreatmentSession $session)
    {
        $this->authorizePatient($patient);
        $this->authorizePatientRelation($patient, $session->patient_id);

        $data = $this->validatedTreatmentSession($request);
        $data['paid'] = $request->boolean('paid');

        $session->update($data);

        return back()->with('status', 'Seduta aggiornata.');
    }

    public function destroyTreatmentSession(Patient $patient, TreatmentSession $session)
    {
        $this->authorizePatient($patient);
        $this->authorizePatientRelation($patient, $session->patient_id);

        $session->delete();

        return back()->with('status', 'Seduta eliminata.');
    }

    public function updateInvoice(Request $request, Patient $patient, Invoice $invoice)
    {
        $this->authorizePatient($patient);
        $this->authorizePatientRelation($patient, $invoice->patient_id);

        $invoice->update($this->validatedInvoice($request));

        return back()->with('status', 'Fattura aggiornata.');
    }

    public function destroyInvoice(Patient $patient, Invoice $invoice)
    {
        $this->authorizePatient($patient);
        $this->authorizePatientRelation($patient, $invoice->patient_id);

        $invoice->delete();

        return back()->with('status', 'Fattura eliminata.');
    }

    private function validatedTreatmentSession(Request $request): array
    {
        return $request->validate([
            'session_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'outcome' => ['nullable', 'string'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'paid' => ['nullable', 'boolean'],
        ]);
    }

    public function storeInvoice(Request $request, Patient $patient)
    {
        $this->authorizePatient($patient);

        $patient->invoices()->create($this->validatedInvoice($request));

        return back()->with('status', 'Fattura registrata.');
    }

    private function validatedInvoice(Request $request): array
    {
        return $request->validate([
            'number' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,sent,paid,cancelled'],
            'description' => ['nullable', 'string'],
        ]);
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

    private function authorizePatientRelation(Patient $patient, int $patientId): void
    {
        abort_unless($patient->id === $patientId, 404);
    }
}
