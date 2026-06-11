<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\TreatmentSession;
use App\Support\PatientExcelExporter;
use App\Support\PatientExcelImporter;
use App\Support\PatientAddressNormalizer;
use App\Support\InvoiceCourtesyPdf;
use App\Support\InvoiceDefaults;
use App\Support\PrivacyConsentPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $patients = Patient::where('user_id', Auth::id())
            ->when($request->search, function ($query, string $search) {
                $terms = collect(preg_split('/\s+/', trim($search)))
                    ->filter()
                    ->values();

                $query->where(function ($query) use ($search, $terms) {
                    $query->where('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('fiscal_code', 'like', "%{$search}%")
                        ->orWhere(function ($query) use ($terms) {
                            foreach ($terms as $term) {
                                $query->where(function ($query) use ($term) {
                                    $query->where('first_name', 'like', "%{$term}%")
                                        ->orWhere('last_name', 'like', "%{$term}%");
                                });
                            }
                        });
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

    public function export(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        $patients = Patient::where('user_id', Auth::id())
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $filename = 'export-pazienti-'.($from ?: 'inizio').'-'.($to ?: 'fine').'.xlsx';

        return response(PatientExcelExporter::make($patients), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'patients_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        $result = PatientExcelImporter::import($validated['patients_file']);

        return redirect()
            ->route('settings.patients')
            ->with('status', "Importazione completata: {$result['created']} creati, {$result['updated']} aggiornati, {$result['skipped']} righe vuote ignorate.");
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
        return $this->folderSection($patient, 'anagrafica');
    }

    public function anamnesis(Patient $patient)
    {
        return $this->folderSection($patient, 'anamnesi');
    }

    public function sessions(Patient $patient)
    {
        return $this->folderSection($patient, 'sedute');
    }

    public function invoices(Patient $patient)
    {
        return $this->folderSection($patient, 'fatture');
    }

    public function privacy(Patient $patient)
    {
        return $this->folderSection($patient, 'privacy');
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

        $data = $request->validate([
            'reason_for_visit' => ['nullable', 'string'],
            'symptoms_started_at' => ['nullable', 'string'],
            'pain_description' => ['nullable', 'string'],
            'irradiation' => ['nullable', 'string'],
            'exams' => ['nullable', 'string'],
            'previous_treatments' => ['nullable', 'string'],
            'traumas' => ['nullable', 'string'],
            'surgeries' => ['nullable', 'string'],
            'visceral_issues' => ['nullable', 'string'],
            'prosthesis_and_devices' => ['nullable', 'string'],
            'orthodontics' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'birth_history' => ['nullable', 'string'],
            'lifestyle' => ['nullable', 'string'],
            'sport' => ['nullable', 'string'],
            'physical_sphere' => ['nullable', 'string'],
            'psychological_sphere' => ['nullable', 'string'],
            'medications' => ['nullable', 'string'],
            'clinical_tests' => ['nullable', 'string'],
            'anamnesis' => ['nullable', 'string'],
            'diagnostic_notes' => ['nullable', 'string'],
            'treatment_plan' => ['nullable', 'string'],
            'contraindications' => ['nullable', 'string'],
        ]);

        $patient->medicalRecord()->updateOrCreate([], $this->compactBlankLines($data));

        return back()->with('status', 'Cartella clinica aggiornata.');
    }

    public function storePrivacyConsent(Request $request, Patient $patient)
    {
        $this->authorizePatient($patient);

        $validated = $request->validate([
            'signed_at' => ['nullable', 'date'],
            'signature_method' => ['nullable', 'string', 'max:255'],
            'signature_data' => ['nullable', 'string'],
            'document_version' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $patient->privacyConsent()->updateOrCreate([], $validated + [
            'privacy_policy_accepted' => $request->boolean('privacy_policy_accepted'),
            'health_data_processing_accepted' => $request->boolean('health_data_processing_accepted'),
            'osteopathic_treatment_accepted' => $request->boolean('osteopathic_treatment_accepted'),
            'doctor_data_sharing_accepted' => $request->boolean('doctor_data_sharing_accepted'),
            'family_data_sharing_accepted' => $request->boolean('family_data_sharing_accepted'),
            'whatsapp_reminders_accepted' => $request->boolean('whatsapp_reminders_accepted'),
            'email_reminders_accepted' => $request->boolean('email_reminders_accepted'),
            'sms_reminders_accepted' => $request->boolean('sms_reminders_accepted'),
            'marketing_accepted' => $request->boolean('marketing_accepted'),
        ]);

        return back()
            ->with('status', 'Consenso privacy aggiornato.')
            ->with('open_privacy_pdf', true);
    }

    public function privacyConsentPdf(Patient $patient)
    {
        $this->authorizePatient($patient);
        $patient->load('privacyConsent');

        $pdf = PrivacyConsentPdf::make($patient, $patient->privacyConsent);
        $path = 'privacy-consents/'.$patient->id.'/consenso-privacy.pdf';
        Storage::disk('local')->put($path, $pdf);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="consenso-privacy-'.$patient->id.'.pdf"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
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

        $invoice->update($this->invoiceData($request, $invoice));
        $invoice->update([
            'description' => $this->invoiceAutomaticDescription($invoice),
        ]);

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
            'pain_level' => ['nullable', 'integer', 'min:0', 'max:10'],
            'outcome' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'paid' => ['nullable', 'boolean'],
        ]);
    }

    private function compactBlankLines(array $data): array
    {
        return collect($data)
            ->map(function ($value) {
                if (! is_string($value)) {
                    return $value;
                }

                $lines = preg_split('/\R/u', str_replace("\r", "\n", $value));
                $lines = collect($lines)
                    ->map(fn (string $line) => trim($line))
                    ->filter(fn (string $line) => $line !== '')
                    ->values();

                return $lines->isEmpty() ? null : $lines->implode("\n");
            })
            ->all();
    }

    public function storeInvoice(Request $request, Patient $patient)
    {
        $this->authorizePatient($patient);

        $invoice = $patient->invoices()->create($this->invoiceData($request));
        $invoice->update([
            'description' => $this->invoiceAutomaticDescription($invoice),
        ]);

        return redirect()
            ->route('patients.invoices.preview', [$patient, $invoice])
            ->with('status', 'Fattura registrata.');
    }

    public function previewInvoice(Patient $patient, Invoice $invoice)
    {
        $this->authorizePatient($patient);
        $this->authorizePatientRelation($patient, $invoice->patient_id);

        return view('patients.invoice-preview', [
            'patient' => $patient,
            'invoice' => $invoice,
            'settings' => InvoiceDefaults::settings(),
            'amounts' => InvoiceDefaults::amounts($invoice),
            'paymentMethods' => InvoiceDefaults::paymentMethods(),
        ]);
    }

    public function pdfInvoice(Patient $patient, Invoice $invoice)
    {
        $this->authorizePatient($patient);
        $this->authorizePatientRelation($patient, $invoice->patient_id);

        $pdf = InvoiceCourtesyPdf::make(
            $patient,
            $invoice,
            InvoiceDefaults::settings(),
            InvoiceDefaults::amounts($invoice),
            InvoiceDefaults::paymentMethods()
        );

        $filename = 'fattura-'.str_replace(['/', '\\'], '-', $invoice->number).'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private function validatedInvoice(Request $request): array
    {
        return $request->validate([
            'number' => ['nullable', 'string', 'max:255'],
            'auto_number_reference' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['required', 'date'],
            'service' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0.01'],
            'line_amount' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'status' => ['required', 'in:draft,sent,paid,cancelled'],
            'description' => ['nullable', 'string'],
        ]);
    }

    private function invoiceData(Request $request, ?Invoice $invoice = null): array
    {
        $data = $this->validatedInvoice($request);
        $issuedAt = now()->parse($data['issued_at']);
        $data['year'] = (int) $issuedAt->format('Y');
        $data['quantity'] = $data['quantity'] ?? 1;
        $data['payment_date'] = $data['payment_date'] ?? $data['issued_at'];
        $autoNumberReference = $data['auto_number_reference'] ?? null;
        unset($data['auto_number_reference']);

        if (blank($data['number'] ?? null) || ($invoice === null && $autoNumberReference && $data['number'] === $autoNumberReference)) {
            $next = InvoiceDefaults::nextNumber($data['year']);
            $data['progressive_number'] = $invoice?->progressive_number ?? $next['progressive_number'];
            $data['number'] = $data['progressive_number'].'/'.$data['year'];
        } elseif (blank($data['progressive_number'] ?? null) && preg_match('/^0*(\d+)/', $data['number'], $matches)) {
            $data['progressive_number'] = (int) $matches[1];
        }

        return $data;
    }

    private function invoiceAutomaticDescription(Invoice $invoice): string
    {
        $amounts = InvoiceDefaults::amounts($invoice);

        return 'IDFattura: '.$invoice->id
            .' | Importo: '.number_format($amounts['line'], 2, '.', '')
            .' | Inps: '.number_format($amounts['social_security'], 2, '.', '')
            .' | Bollo: '.number_format($amounts['stamp'], 2, '.', '');
    }

    private function validatedPatient(Request $request): array
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:50'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'fiscal_code' => ['nullable', 'string', 'max:16'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'pec' => ['nullable', 'email', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'string', 'max:2'],
            'address' => ['nullable', 'string', 'max:255'],
            'street_number' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:2'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
            'customer_type' => ['nullable', 'in:Privato,Pubblica amministrazione'],
            'telematic_address' => ['nullable', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:32'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'eori_code' => ['nullable', 'string', 'max:32'],
        ]);

        $data['country_id'] = $data['country_id'] ?? 'IT';
        $data['customer_type'] = $data['customer_type'] ?? 'Privato';
        $data['telematic_address'] = ($data['telematic_address'] ?? null) ?: '0000000';

        return PatientAddressNormalizer::normalize($data);
    }

    private function authorizePatient(Patient $patient): void
    {
        abort_unless($patient->user_id === Auth::id(), 404);
    }

    private function folderSection(Patient $patient, string $section)
    {
        $this->authorizePatient($patient);

        $patient->load(['medicalRecord', 'treatmentSessions', 'invoices', 'privacyConsent']);

        return view('patients.show', compact('patient', 'section'));
    }

    private function authorizePatientRelation(Patient $patient, int $patientId): void
    {
        abort_unless($patient->id === $patientId, 404);
    }
}
