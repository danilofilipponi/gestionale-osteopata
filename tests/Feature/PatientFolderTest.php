<?php

namespace Tests\Feature;

use App\Models\Patient;
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
        ]);

        $this->actingAs($user)
            ->get(route('patients.show', $patient))
            ->assertOk()
            ->assertSee('Dati clinici iniziali')
            ->assertDontSee('Registra seduta')
            ->assertDontSee('Registra fattura');

        $this->actingAs($user)
            ->get(route('patients.sessions.index', $patient))
            ->assertOk()
            ->assertSee('Storico delle sedute')
            ->assertDontSee('Dati clinici iniziali');

        $this->actingAs($user)
            ->get(route('patients.invoices.index', $patient))
            ->assertOk()
            ->assertSee('Storico delle fatture emesse')
            ->assertDontSee('Dati clinici iniziali');

        $this->actingAs($user)
            ->get(route('patients.privacy.index', $patient))
            ->assertOk()
            ->assertSee('Privacy e consenso')
            ->assertDontSee('Dati clinici iniziali');
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
            'number' => '0001/2026',
            'progressive_number' => 1,
            'year' => 2026,
        ]);
    }
}
