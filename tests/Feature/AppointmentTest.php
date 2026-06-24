<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointments_page_is_protected(): void
    {
        $this->get(route('appointments.index'))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_appointment_can_be_created_for_patient(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        $this->actingAs($user)
            ->post(route('appointments.store'), [
                'patient_id' => $patient->id,
                'title' => 'Prima visita',
                'starts_at' => '2026-06-06 09:00:00',
                'ends_at' => '2026-06-06 10:00:00',
                'type' => 'visit',
                'status' => 'scheduled',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'title' => 'Prima visita',
            'status' => 'scheduled',
        ]);
    }

    public function test_overlapping_appointments_are_allowed(): void
    {
        $user = User::factory()->create();

        Appointment::create([
            'title' => 'Visita',
            'starts_at' => '2026-06-06 09:00:00',
            'ends_at' => '2026-06-06 10:00:00',
            'type' => 'visit',
            'status' => 'scheduled',
        ]);

        $this->actingAs($user)
            ->post(route('appointments.store'), [
                'title' => 'Sovrapposto',
                'starts_at' => '2026-06-06 09:30:00',
                'ends_at' => '2026-06-06 10:30:00',
                'type' => 'visit',
                'status' => 'scheduled',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appointments', [
            'title' => 'Sovrapposto',
            'starts_at' => '2026-06-06 09:30:00',
            'ends_at' => '2026-06-06 10:30:00',
        ]);
    }

    public function test_personal_category_is_available_when_saved_categories_do_not_include_it(): void
    {
        $user = User::factory()->create();

        Setting::setValue('agenda_categories', json_encode([
            [
                'key' => 'Cagli',
                'label' => 'Visita Cagli',
                'color' => '#111111',
                'google_calendar_id' => 'calendar-cagli',
                'sync_patients' => true,
            ],
        ]), 'agenda');

        Appointment::create([
            'title' => 'Evento personale',
            'starts_at' => '2026-06-06 09:00:00',
            'ends_at' => '2026-06-06 10:00:00',
            'type' => 'personal',
            'status' => 'scheduled',
        ]);

        $this->actingAs($user)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('value="personal"', false);
    }

}
