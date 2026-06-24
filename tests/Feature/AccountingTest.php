<?php

namespace Tests\Feature;

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
}
