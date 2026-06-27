<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use ReflectionClass;
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

    public function test_multi_day_appointment_is_available_on_every_calendar_day(): void
    {
        $user = User::factory()->create();
        $appointment = Appointment::create([
            'title' => 'Evento di più giorni',
            'starts_at' => '2026-06-12 15:00:00',
            'ends_at' => '2026-06-14 15:00:00',
            'type' => 'personal',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($user)->get(route('appointments.index', [
            'view' => 'week',
            'date' => '2026-06-12',
        ]));

        $response->assertOk();
        $appointmentsByDate = $response->viewData('appointmentsByDate');

        $containsAppointment = fn ($appointments) => $appointments->contains(
            fn (Appointment $candidate) => $candidate->id === $appointment->id,
        );

        $this->assertTrue($containsAppointment($appointmentsByDate->get('2026-06-12')));
        $this->assertTrue($containsAppointment($appointmentsByDate->get('2026-06-13')));
        $this->assertTrue($containsAppointment($appointmentsByDate->get('2026-06-14')));
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

    public function test_patient_match_modal_auto_opens_only_when_session_requests_it(): void
    {
        $user = User::factory()->create();

        Appointment::create([
            'title' => 'Rossi Mario',
            'starts_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(9, 45),
            'type' => 'visit',
            'status' => 'scheduled',
            'google_event_id' => 'google-event-1',
            'patient_match_status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('id="patient-match-modal"', false)
            ->assertDontSee('<div id="patient-match-modal" data-auto-open-patient-match', false);

        $this->actingAs($user)
            ->withSession(['show_patient_match_modal' => true])
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('<div id="patient-match-modal" data-auto-open-patient-match', false);

        $this->actingAs($user)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertDontSee('<div id="patient-match-modal" data-auto-open-patient-match', false);
    }

    public function test_patient_match_modal_stays_open_after_matching_action(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);
        $appointment = Appointment::create([
            'title' => 'Rossi Mario',
            'starts_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(9, 45),
            'type' => 'visit',
            'status' => 'scheduled',
            'google_event_id' => 'google-event-1',
            'patient_match_status' => 'pending',
        ]);

        $this->actingAs($user)
            ->patch(route('appointments.patient-match.resolve', $appointment), [
                'patient_id' => $patient->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('keep_patient_match_modal_open', true);

        Appointment::create([
            'title' => 'Bianchi Luca',
            'starts_at' => now()->addDays(2)->setTime(9, 0),
            'ends_at' => now()->addDays(2)->setTime(9, 45),
            'type' => 'visit',
            'status' => 'scheduled',
            'google_event_id' => 'google-event-2',
            'patient_match_status' => 'pending',
        ]);

        $this->actingAs($user)
            ->withSession(['keep_patient_match_modal_open' => true])
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('<div id="patient-match-modal" data-auto-open-patient-match', false);
    }

    public function test_google_calendar_sync_requires_reconnect_when_token_is_revoked(): void
    {
        config([
            'services.google_calendar.client_id' => 'client-id',
            'services.google_calendar.client_secret' => 'client-secret',
            'services.google_calendar.redirect_uri' => 'http://127.0.0.1:8000/google/calendar/callback',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Token has been expired or revoked.',
            ], 400),
        ]);

        $user = User::factory()->create();
        Setting::setValue('google_calendar_enabled', '1', 'agenda');
        Setting::setValue('google_calendar_sync_mode', 'read', 'agenda');
        Setting::setValue('google_calendar_access_token', 'expired-token', 'agenda');
        Setting::setValue('google_calendar_refresh_token', 'revoked-refresh-token', 'agenda');
        Setting::setValue('google_calendar_token_expires_at', now()->subHour()->toDateTimeString(), 'agenda');
        Setting::setValue('google_calendar_selected_ids', json_encode(['calendar-1']), 'agenda');

        $this->actingAs($user)
            ->post(route('google.calendar.sync'), ['sync_year' => 2026])
            ->assertRedirect(route('settings.agenda'))
            ->assertSessionHas('status', fn (string $message) => str_contains($message, 'nuovo collegamento'));

        $this->assertNull(Setting::getValue('google_calendar_access_token'));
        $this->assertNull(Setting::getValue('google_calendar_refresh_token'));
    }

    public function test_google_calendar_category_is_kept_even_without_patient_sync(): void
    {
        Setting::setValue('agenda_categories', json_encode([
            [
                'key' => 'work',
                'label' => 'Memo lavoro',
                'color' => '#ffcc00',
                'google_calendar_id' => 'calendar-work',
                'sync_patients' => false,
            ],
        ]), 'agenda');

        $category = $this->callGoogleCategoryForCalendar('calendar-work');

        $this->assertSame('work', $category['key']);
        $this->assertFalse($category['sync_patients']);
    }

    public function test_google_calendar_without_category_uses_other(): void
    {
        Setting::setValue('agenda_categories', json_encode([
            [
                'key' => 'visit',
                'label' => 'Visita osteopatica',
                'color' => '#8bd9e8',
                'google_calendar_id' => 'primary-calendar',
                'sync_patients' => true,
            ],
        ]), 'agenda');

        $category = $this->callGoogleCategoryForCalendar('calendar-without-category');

        $this->assertSame('other', $category['key']);
        $this->assertFalse($category['sync_patients']);
    }

    public function test_google_patient_matching_only_auto_matches_unique_perfect_match(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        $result = $this->callGooglePatientMatcher('Rossi Mario');

        $this->assertSame('matched', $result['status']);
        $this->assertTrue($patient->is($result['patient']));
    }

    public function test_google_patient_matching_keeps_duplicates_pending_even_with_perfect_match(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);
        Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'phone' => '3331234567',
        ]);

        $result = $this->callGooglePatientMatcher('Rossi Mario');

        $this->assertSame('pending', $result['status']);
        $this->assertNull($result['patient']);
    }

    public function test_google_patient_matching_keeps_partial_matches_pending(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        $result = $this->callGooglePatientMatcher('Rossi');

        $this->assertSame('pending', $result['status']);
        $this->assertNull($result['patient']);
    }

    private function callGooglePatientMatcher(string $title): array
    {
        $controller = app(\App\Http\Controllers\GoogleCalendarController::class);
        $method = (new ReflectionClass($controller))->getMethod('matchPatient');
        $method->setAccessible(true);

        return $method->invoke($controller, $title);
    }

    private function callGoogleCategoryForCalendar(string $calendarId): array
    {
        $controller = app(\App\Http\Controllers\GoogleCalendarController::class);
        $method = (new ReflectionClass($controller))->getMethod('categoryForCalendar');
        $method->setAccessible(true);

        return $method->invoke($controller, $calendarId);
    }

}
