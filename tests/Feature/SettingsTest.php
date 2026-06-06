<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
