<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_is_protected(): void
    {
        $this->get('/settings')
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_settings_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('settings.update'), [
                'practice_name' => 'Studio Test',
                'practice_owner' => 'Mario Rossi',
                'practice_email' => 'studio@example.com',
                'practice_phone' => '3331234567',
                'practice_address' => 'Via Roma 1',
                'vat_number' => 'IT12345678901',
                'tax_code' => 'RSSMRA80A01H501U',
                'invoice_prefix' => 'FT',
                'default_session_fee' => '70',
                'appointment_duration' => '60',
            ])
            ->assertRedirect();

        $this->assertSame('Studio Test', Setting::getValue('practice_name'));
        $this->assertSame('FT', Setting::getValue('invoice_prefix'));
    }

    public function test_users_can_be_created_from_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.users.store'), [
                'name' => 'Segreteria',
                'email' => 'segreteria@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'segreteria@example.com',
            'name' => 'Segreteria',
        ]);
    }

    public function test_user_password_can_be_updated_from_settings(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create([
            'email' => 'operatore@example.com',
        ]);

        $this->actingAs($admin)
            ->patch(route('settings.users.update', $user), [
                'name' => 'Operatore',
                'email' => 'operatore@example.com',
                'password' => 'nuova-password',
                'password_confirmation' => 'nuova-password',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('nuova-password', $user->fresh()->password));
    }

    public function test_last_user_cannot_be_deleted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('settings.users.destroy', $user))
            ->assertStatus(422);

        $this->assertDatabaseCount('users', 1);
    }
}
