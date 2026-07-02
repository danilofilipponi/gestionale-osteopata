<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_summary_uses_previous_year_november_advance_for_july_taxes(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        Invoice::create([
            'patient_id' => $patient->id,
            'number' => '1/2024',
            'progressive_number' => 1,
            'year' => 2024,
            'issued_at' => '2024-06-01',
            'service' => 'Seduta osteopatica',
            'amount' => 1000,
            'status' => 'paid',
        ]);

        Invoice::create([
            'patient_id' => $patient->id,
            'number' => '1/2025',
            'progressive_number' => 1,
            'year' => 2025,
            'issued_at' => '2025-06-01',
            'service' => 'Seduta osteopatica',
            'amount' => 10000,
            'status' => 'paid',
        ]);

        $this->actingAs($user)
            ->get(route('accounting.index', ['year' => 2025]))
            ->assertOk()
            ->assertSee('Acconto novembre 2024')
            ->assertSee('€ 151,26')
            ->assertSee('Tot. tasse Luglio')
            ->assertSee('€ 3.045,18');
    }

    public function test_session_is_not_counted_as_to_invoice_when_same_day_invoice_exists(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        Invoice::create([
            'patient_id' => $patient->id,
            'number' => '1/2026',
            'progressive_number' => 1,
            'year' => 2026,
            'issued_at' => '2026-06-10',
            'service' => 'Seduta osteopatica',
            'amount' => 40,
            'status' => 'paid',
        ]);
        $patient->treatmentSessions()->create([
            'session_date' => '2026-06-10',
            'title' => 'Seduta osteopatica',
            'fee' => 40,
            'paid' => false,
        ]);
        $patient->treatmentSessions()->create([
            'session_date' => '2026-06-11',
            'title' => 'Seduta osteopatica',
            'fee' => 35,
            'paid' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('accounting.index', ['year' => 2026]))
            ->assertOk();

        $june = $response->viewData('monthlyRows')->firstWhere('month', 6);

        $this->assertSame(40.0, $june['invoiced']);
        $this->assertSame(35.0, $june['to_invoice']);
        $this->assertCount(1, $june['to_invoice_sessions']);
        $this->assertSame('2026-06-11', $june['to_invoice_sessions']->first()->session_date->toDateString());
    }

    public function test_no_show_appointment_session_is_not_counted_as_to_invoice(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'title' => $patient->list_name,
            'starts_at' => '2026-06-12 09:00:00',
            'ends_at' => '2026-06-12 09:45:00',
            'type' => 'visit',
            'status' => 'no_show',
        ]);
        $patient->treatmentSessions()->create([
            'appointment_id' => $appointment->id,
            'session_date' => '2026-06-12',
            'title' => 'Seduta osteopatica',
            'fee' => 40,
            'paid' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('accounting.index', ['year' => 2026]))
            ->assertOk();

        $june = $response->viewData('monthlyRows')->firstWhere('month', 6);

        $this->assertSame(0.0, $june['to_invoice']);
        $this->assertCount(0, $june['to_invoice_sessions']);
    }
}
