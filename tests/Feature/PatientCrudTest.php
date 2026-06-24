<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Support\PatientExcelExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Tests\TestCase;
use ZipArchive;

class PatientCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_patient_page_shows_guided_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('patients.create'))
            ->assertOk()
            ->assertSee('Dati anagrafici')
            ->assertSee('Scheda paziente')
            ->assertSee('Eta:')
            ->assertSee('Luogo di nascita')
            ->assertSee('Calcolato automaticamente')
            ->assertSee('Dati fiscali ed esportazione')
            ->assertSee('Consiglio operativo')
            ->assertSee('data-unsaved-warning="patient-form"', false);
    }

    public function test_patient_can_be_created_from_guided_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('patients.store'), [
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'birth_date' => '1980-01-15',
                'gender' => 'M',
                'birth_place' => 'Roma',
                'fiscal_code' => 'rssmra80a15h501u',
                'phone' => '3331234567',
                'email' => 'mario.rossi@example.com',
                'profession' => 'Impiegato',
                'address' => 'Via Roma',
                'street_number' => '1',
                'city' => 'Roma',
                'province' => 'RM',
                'postal_code' => '00100',
                'notes' => 'Primo contatto.',
            ]);

        $patient = Patient::where('email', 'mario.rossi@example.com')->first();

        $this->assertNotNull($patient);
        $response->assertRedirect(route('patients.anamnesis.index', $patient, false));
        $this->assertDatabaseHas('patients', [
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'mario.rossi@example.com',
            'street_number' => '1',
            'customer_type' => 'Privato',
            'telematic_address' => '0000000',
        ]);
    }

    public function test_patient_created_from_appointment_link_is_matched_to_appointment(): void
    {
        $user = User::factory()->create();
        $appointment = Appointment::create([
            'title' => 'Bianchi Luisa',
            'starts_at' => '2026-06-06 09:00:00',
            'ends_at' => '2026-06-06 10:00:00',
            'type' => 'visit',
            'status' => 'scheduled',
        ]);

        $this->actingAs($user)
            ->post(route('patients.store'), [
                'appointment_id' => $appointment->id,
                'first_name' => 'Luisa',
                'last_name' => 'Bianchi',
                'phone' => '3331234567',
                'email' => 'luisa@example.com',
            ])
            ->assertRedirect();

        $patient = Patient::where('email', 'luisa@example.com')->first();

        $this->assertNotNull($patient);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'patient_id' => $patient->id,
            'title' => 'Bianchi Luisa',
            'patient_match_status' => 'matched',
        ]);
    }

    public function test_combined_residence_is_split_when_patient_is_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('patients.store'), [
                'first_name' => 'Milena',
                'last_name' => 'Rossi',
                'address' => 'Via Roma 78, 67051 Avezzano - AQ',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'user_id' => $user->id,
            'first_name' => 'Milena',
            'last_name' => 'Rossi',
            'address' => 'Via Roma',
            'street_number' => '78',
            'city' => 'Avezzano',
            'province' => 'AQ',
            'postal_code' => '67051',
        ]);
    }

    public function test_edit_patient_page_uses_guided_form(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'phone' => '3331234567',
        ]);

        $this->actingAs($user)
            ->get(route('patients.edit', $patient))
            ->assertOk()
            ->assertSee('Dati anagrafici')
            ->assertSee('Mario')
            ->assertSee('Rossi')
            ->assertSee('Elimina paziente');
    }

    public function test_patients_can_be_searched_by_full_name(): void
    {
        $user = User::factory()->create();
        Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Elisa',
            'last_name' => 'Vitali',
        ]);
        Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        $this->actingAs($user)
            ->get(route('patients.index', ['search' => 'Elisa Vitali']))
            ->assertOk()
            ->assertSee('Vitali Elisa')
            ->assertDontSee('Rossi Mario');
    }

    public function test_patients_can_be_exported_to_import_excel_layout(): void
    {
        $user = User::factory()->create();
        Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'phone' => '3331234567',
            'email' => 'mario.rossi@example.com',
            'pec' => 'mario.rossi@pec.it',
            'country_id' => 'IT',
            'fiscal_code' => 'RSSMRA80A15H501U',
            'address' => 'Via Roma',
            'street_number' => '1',
            'city' => 'Roma',
            'province' => 'RM',
            'postal_code' => '00100',
            'customer_type' => 'Privato',
            'telematic_address' => '0000000',
        ]);

        $response = $this->actingAs($user)
            ->get(route('patients.export', ['from' => '2026-06-01', 'to' => '2026-06-30']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('Content-Disposition', 'attachment; filename="export-pazienti-2026-06-01-2026-06-30.xlsx"');

        $path = tempnam(sys_get_temp_dir(), 'patients-export-test-');
        file_put_contents($path, $response->getContent());

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($path);

        $this->assertStringContainsString('Codice cliente', $sheet);
        $this->assertStringContainsString('Indirizzo telematico (Codice SDI o PEC)', $sheet);
        $this->assertStringContainsString('Privato', $sheet);
        $this->assertStringContainsString('0000000', $sheet);
        $this->assertStringContainsString('mario.rossi@pec.it', $sheet);
        $this->assertStringContainsString('Via Roma', $sheet);
        $this->assertStringContainsString('sqref="G2:G1000 N2:N1000"', $sheet);
        $this->assertStringContainsString('Valori!$B$2:$B$250', $sheet);
    }

    public function test_patients_can_be_imported_from_export_excel_layout(): void
    {
        $user = User::factory()->create();
        $patient = new Patient([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'phone' => '3331234567',
            'email' => 'mario.rossi@example.com',
            'pec' => 'mario.rossi@pec.it',
            'country_id' => 'IT',
            'fiscal_code' => 'RSSMRA80A15H501U',
            'address' => 'Via Roma',
            'street_number' => '1',
            'city' => 'Roma',
            'province' => 'RM',
            'postal_code' => '00100',
            'customer_type' => 'Privato',
            'telematic_address' => '0000000',
            'vat_number' => '12345678901',
            'business_name' => '',
            'eori_code' => '',
        ]);
        $patient->id = 999;

        $path = tempnam(sys_get_temp_dir(), 'patients-import-test-').'.xlsx';
        file_put_contents($path, PatientExcelExporter::make(new Collection([$patient])));

        $this->actingAs($user)
            ->post(route('patients.import'), [
                'patients_file' => new UploadedFile(
                    $path,
                    'import-pazienti.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ])
            ->assertRedirect(route('settings.patients'));

        unlink($path);

        $this->assertDatabaseHas('patients', [
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'mario.rossi@example.com',
            'pec' => 'mario.rossi@pec.it',
            'vat_number' => '12345678901',
            'telematic_address' => '0000000',
        ]);
    }
}
