<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientFolderTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_folder_contains_main_sections(): void
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
            ->assertSee('Storico delle sedute')
            ->assertSee('Storico delle fatture emesse')
            ->assertSee('Privacy e consenso');
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
}
