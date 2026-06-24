<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientFolderTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_folder_navigation_links_to_separate_sections(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        $this->actingAs($user)
            ->get(route('patients.show', $patient))
            ->assertOk()
            ->assertSee('Anagrafica')
            ->assertSee('Anamnesi')
            ->assertSee('Storico sedute')
            ->assertSee('Storico fatture')
            ->assertSee('Privacy e consenso');
    }

    public function test_patient_folder_sections_are_separate_pages(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'address' => 'Via Roma',
            'street_number' => '1',
            'city' => 'Roma',
            'province' => 'RM',
            'postal_code' => '00100',
        ]);

        $this->actingAs($user)
            ->get(route('patients.show', $patient))
            ->assertOk()
            ->assertSee('Anagrafica paziente')
            ->assertSeeInOrder(['Residenza', 'Indirizzo', 'Via Roma', 'Civico', '1', 'Citta', 'Roma', 'Provincia', 'RM', 'CAP', '00100'])
            ->assertDontSee('Salva anamnesi')
            ->assertDontSee('Registra seduta')
            ->assertDontSee('Registra fattura');

        $this->actingAs($user)
            ->get(route('patients.anamnesis.index', $patient))
            ->assertOk()
            ->assertSee('Cartella clinica')
            ->assertSee('Salva anamnesi')
            ->assertSee('data-unsaved-warning="anamnesis-form"', false)
            ->assertDontSee('Modifica dati');

        $this->actingAs($user)
            ->get(route('patients.sessions.index', $patient))
            ->assertOk()
            ->assertSee('Storico delle sedute')
            ->assertDontSee('Salva anamnesi');

        $this->actingAs($user)
            ->get(route('patients.invoices.index', $patient))
            ->assertOk()
            ->assertSee('Storico delle fatture emesse')
            ->assertDontSee('Salva anamnesi');

        $this->actingAs($user)
            ->get(route('patients.privacy.index', $patient))
            ->assertOk()
            ->assertSee('Privacy e consenso')
            ->assertDontSee('Salva anamnesi');
    }

    public function test_privacy_consent_can_be_saved(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        $this->actingAs($user)
            ->post(route('patients.privacy-consent.store', $patient), [
                'privacy_policy_accepted' => '1',
                'health_data_processing_accepted' => '1',
                'marketing_accepted' => '0',
                'signed_at' => '2026-06-06',
                'signature_method' => 'cartaceo',
                'document_version' => 'privacy-v1',
                'notes' => 'Consenso firmato in studio.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('privacy_consents', [
            'patient_id' => $patient->id,
            'privacy_policy_accepted' => true,
            'health_data_processing_accepted' => true,
            'signature_method' => 'cartaceo',
            'document_version' => 'privacy-v1',
        ]);
    }

    public function test_extended_medical_record_can_be_saved(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        $this->actingAs($user)
            ->post(route('patients.medical-record.store', $patient), [
                'reason_for_visit' => 'Lombalgia',
                'symptoms_started_at' => '2026-06-01',
                'pain_description' => 'Dolore lombare',
                'irradiation' => 'Gamba destra',
                'clinical_tests' => 'Test positivo',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('medical_records', [
            'patient_id' => $patient->id,
            'reason_for_visit' => 'Lombalgia',
            'pain_description' => 'Dolore lombare',
        ]);
    }

    public function test_invoice_number_is_generated_when_missing(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        $this->actingAs($user)
            ->post(route('patients.invoices.store', $patient), [
                'issued_at' => '2026-06-06',
                'service' => 'Seduta osteopatica',
                'amount' => '70',
                'payment_method' => 'Carta',
                'status' => 'paid',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'patient_id' => $patient->id,
            'number' => '1/2026',
            'progressive_number' => 1,
            'year' => 2026,
        ]);
    }

    public function test_patient_invoice_form_uses_defaults_and_opens_preview(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Elisa',
            'last_name' => 'Vitali',
            'fiscal_code' => 'VTLLES80A41D488Z',
        ]);

        Setting::setValue('invoice_payment_method', 'MP05', 'invoice');
        Setting::setValue('invoice_services', json_encode([
            [
                'name' => 'Seduta di manipolazione osteopatica',
                'description' => 'Seduta di manipolazione osteopatica',
                'amount' => 50,
                'vat_rate' => 0,
                'social_security_rate' => 4,
                'vat_nature' => 'N2.2',
                'unit_measure' => 'PZ',
                'stamp_duty' => true,
            ],
        ]), 'invoice');

        $this->actingAs($user)
            ->get(route('patients.invoices.index', $patient))
            ->assertOk()
            ->assertSee('1/'.now()->format('Y'))
            ->assertSee('Seduta di manipolazione osteopatica')
            ->assertSee('MP05 - Bonifico')
            ->assertSee('Aliquota cassa')
            ->assertSee('IVA / Natura')
            ->assertSee('Bollo')
            ->assertSee('Data pagamento')
            ->assertSee('IDFattura:');

        $response = $this->actingAs($user)
            ->post(route('patients.invoices.store', $patient), [
                'issued_at' => now()->toDateString(),
                'service' => 'Seduta di manipolazione osteopatica',
                'quantity' => 2,
                'line_amount' => 100,
                'amount' => 106,
                'payment_method' => 'MP05',
                'payment_date' => now()->toDateString(),
                'status' => 'paid',
                'description' => 'Seduta di manipolazione osteopatica',
            ]);

        $invoice = $patient->invoices()->firstOrFail();
        $response->assertRedirect(route('patients.invoices.preview', [$patient, $invoice]));

        $this->actingAs($user)
            ->get(route('patients.invoices.preview', [$patient, $invoice]))
            ->assertOk()
            ->assertSee('Anteprima fattura')
            ->assertSee('Stampa')
            ->assertSee('Quantita: 2')
            ->assertDontSee('IDFattura:')
            ->assertDontSee('Inps: 4.00')
            ->assertSee('Bonifico')
            ->assertSee(now()->format('d/m/Y'))
            ->assertSee('Pagata')
            ->assertSee('EUR 106,00');

        $this->actingAs($user)
            ->get(route('patients.invoices.index', $patient))
            ->assertOk()
            ->assertSee('Stampa')
            ->assertSee('Bonifico')
            ->assertSee('Pagata');
    }

    public function test_auto_invoice_number_is_recalculated_when_form_reference_is_stale(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Elisa',
            'last_name' => 'Vitali',
        ]);

        $patient->invoices()->create([
            'number' => '273/2026',
            'year' => 2026,
            'progressive_number' => 273,
            'issued_at' => '2026-06-06',
            'service' => 'Seduta di manipolazione osteopatica',
            'quantity' => 1,
            'amount' => 38.46,
            'status' => 'paid',
        ]);

        $this->actingAs($user)
            ->post(route('patients.invoices.store', $patient), [
                'number' => '0001/2026',
                'auto_number_reference' => '0001/2026',
                'issued_at' => '2026-06-07',
                'service' => 'Seduta di manipolazione osteopatica',
                'quantity' => 1,
                'amount' => 38.46,
                'payment_method' => 'MP08',
                'payment_date' => '2026-06-07',
                'status' => 'paid',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'patient_id' => $patient->id,
            'number' => '274/2026',
            'progressive_number' => 274,
        ]);
    }
}
