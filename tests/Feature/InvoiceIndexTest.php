<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoices_page_is_protected(): void
    {
        $this->get(route('invoices.index'))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_invoices_page_shows_summary_and_list(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'fiscal_code' => 'RSSMRA80A01H501U',
        ]);

        Invoice::create([
            'patient_id' => $patient->id,
            'number' => '3/2026',
            'year' => 2026,
            'progressive_number' => 3,
            'issued_at' => '2026-06-08',
            'service' => 'Seduta di manipolazione osteopatica',
            'amount' => '40',
            'status' => 'paid',
            'xml_downloaded_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('Fatture')
            ->assertSee('Totale fatturato')
            ->assertSee('3/2026')
            ->assertSee('Apri dettaglio fattura')
            ->assertDontSee('Progressivo')
            ->assertSee('Rossi Mario')
            ->assertSee('€ 40,00')
            ->assertSee('Inviata')
            ->assertSee('XML scaricato')
            ->assertSee('Impostazioni fatture');
    }

    public function test_invoices_page_can_be_filtered_by_search(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);
        $otherPatient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Luisa',
            'last_name' => 'Bianchi',
        ]);

        Invoice::create([
            'patient_id' => $patient->id,
            'number' => '3/2026',
            'issued_at' => '2026-06-08',
            'service' => 'Seduta osteopatica',
            'amount' => '40',
            'status' => 'paid',
        ]);
        Invoice::create([
            'patient_id' => $otherPatient->id,
            'number' => '4/2026',
            'issued_at' => '2026-06-09',
            'service' => 'Seduta osteopatica',
            'amount' => '40',
            'status' => 'paid',
        ]);

        $this->actingAs($user)
            ->get(route('invoices.index', ['search' => 'Mario']))
            ->assertOk()
            ->assertSee('Rossi Mario')
            ->assertDontSee('Bianchi Luisa');
    }

    public function test_invoice_search_ignores_selected_year_and_month(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);
        $otherPatient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Luisa',
            'last_name' => 'Bianchi',
        ]);

        Invoice::create([
            'patient_id' => $patient->id,
            'number' => '3/2026',
            'issued_at' => '2026-05-08',
            'service' => 'Seduta osteopatica',
            'amount' => '40',
            'status' => 'paid',
        ]);
        Invoice::create([
            'patient_id' => $otherPatient->id,
            'number' => '4/2026',
            'issued_at' => '2026-06-09',
            'service' => 'Seduta osteopatica',
            'amount' => '40',
            'status' => 'paid',
        ]);

        $this->actingAs($user)
            ->get(route('invoices.index', [
                'search' => 'Mario',
                'summary_year' => 2026,
                'summary_month' => 6,
            ]))
            ->assertOk()
            ->assertSee('Rossi Mario')
            ->assertSee('3/2026')
            ->assertDontSee('Bianchi Luisa')
            ->assertDontSee('4/2026');
    }

    public function test_invoice_list_shows_red_x_when_xml_is_not_downloaded(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        Invoice::create([
            'patient_id' => $patient->id,
            'number' => '5/2026',
            'issued_at' => '2026-06-10',
            'service' => 'Seduta osteopatica',
            'amount' => '40',
            'status' => 'paid',
            'xml_downloaded_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('XML non scaricato')
            ->assertSee('×');
    }

    public function test_invoice_summary_uses_selected_year_and_month(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        Invoice::create([
            'patient_id' => $patient->id,
            'number' => '10/2026',
            'issued_at' => '2026-06-07',
            'service' => 'Seduta osteopatica',
            'amount' => '80',
            'status' => 'paid',
        ]);
        Invoice::create([
            'patient_id' => $patient->id,
            'number' => '11/2026',
            'issued_at' => '2026-05-10',
            'service' => 'Seduta osteopatica',
            'amount' => '40',
            'status' => 'paid',
        ]);
        Invoice::create([
            'patient_id' => $patient->id,
            'number' => '3/2025',
            'issued_at' => '2025-01-08',
            'service' => 'Seduta osteopatica',
            'amount' => '40',
            'status' => 'paid',
        ]);

        $this->actingAs($user)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('€ 120,00')
            ->assertSee('€ 80,00')
            ->assertSee('10/2026')
            ->assertDontSee('11/2026')
            ->assertSee('2025')
            ->assertSee('Fatture di Giugno 2026')
            ->assertSee('Giugno');

        $this->actingAs($user)
            ->get(route('invoices.index', [
                'summary_year' => 2025,
                'summary_month' => 1,
            ]))
            ->assertOk()
            ->assertSee('€ 40,00')
            ->assertSee('3/2025')
            ->assertSee('Fatture di Gennaio 2025')
            ->assertSee('Gennaio');
    }
}
