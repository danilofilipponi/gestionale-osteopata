<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Setting;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Support\ApplicationBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use ZipArchive;

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

    public function test_settings_sections_are_separate_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Dati studio')
            ->assertSee('Salva impostazioni')
            ->assertSee('Impostazioni pazienti')
            ->assertSee('Utenti e password')
            ->assertDontSee('Esporta Excel pazienti')
            ->assertDontSee('Nuovo utente');

        $this->actingAs($user)
            ->get(route('settings.patients'))
            ->assertOk()
            ->assertSee('Esporta Excel pazienti')
            ->assertSee('Importazione Excel pazienti')
            ->assertSee('Importa Excel pazienti')
            ->assertDontSee('Salva impostazioni')
            ->assertDontSee('Nuovo utente');

        $this->actingAs($user)
            ->get(route('settings.users'))
            ->assertOk()
            ->assertSee('Nuovo utente')
            ->assertSee('Utenti esistenti')
            ->assertDontSee('Esporta Excel pazienti')
            ->assertDontSee('Salva impostazioni');

        $this->actingAs($user)
            ->get(route('settings.invoices'))
            ->assertOk()
            ->assertSee('Impostazioni default fatture')
            ->assertSee('Cedente / prestatore')
            ->assertSee('Filipponi Danilo')
            ->assertSee('Seduta di manipolazione osteopatica')
            ->assertSee('Servizi selezionabili in fattura')
            ->assertSee('Esportazione XML fatture')
            ->assertSee('Esporta fatture XML')
            ->assertSee('Importazione Excel fatture')
            ->assertSee('Importa Excel fatture')
            ->assertDontSee('Esporta Excel pazienti')
            ->assertDontSee('Nuovo utente');
    }

    public function test_invoice_settings_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('settings.invoices.update'), [
                'invoice_transmission_format' => 'FPR12',
                'invoice_document_type' => 'TD01',
                'invoice_currency' => 'EUR',
                'invoice_default_recipient_code' => '0000000',
                'invoice_transmitter_country_id' => 'IT',
                'invoice_transmitter_vat_number' => '01879020517',
                'invoice_sender_vat_country' => 'IT',
                'invoice_sender_vat_number' => '02429900414',
                'invoice_sender_tax_code' => 'FLPDNL85R01D488C',
                'invoice_sender_name' => 'Filipponi Danilo',
                'invoice_sender_address' => 'via Madonna Ponte 33',
                'invoice_sender_postal_code' => '61032',
                'invoice_sender_city' => 'Fano',
                'invoice_sender_province' => 'PU',
                'invoice_sender_country' => 'IT',
                'invoice_sender_email' => 'danilo.filipponi@gmail.com',
                'invoice_tax_regime' => 'RF19',
                'invoice_vat_nature' => 'N2.2',
                'invoice_vat_reference' => 'Non soggette - altri casi',
                'invoice_social_security_type' => 'TC22',
                'invoice_social_security_rate' => '4.00',
                'invoice_payment_method' => 'MP08',
                'invoice_payment_terms' => 'TP02',
                'invoice_stamp_threshold' => '77.47',
                'invoice_stamp_amount' => '2.00',
                'invoice_default_causale' => 'Operazione non soggetta a ritenuta alla fonte',
                'services' => [
                    [
                        'name' => 'Seduta di manipolazione osteopatica',
                        'description' => 'Trattamento individuale',
                        'amount' => '38.46',
                        'vat_rate' => '0',
                        'social_security_rate' => '4.00',
                        'vat_nature' => 'N2.2',
                        'unit_measure' => 'PZ',
                        'stamp_duty' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('settings.invoices'));

        $this->assertSame('FPR12', Setting::getValue('invoice_transmission_format'));
        $this->assertSame('MP08', Setting::getValue('invoice_payment_method'));
        $this->assertSame('Filipponi Danilo', Setting::getValue('invoice_sender_name'));
        $this->assertStringContainsString('Seduta di manipolazione osteopatica', Setting::getValue('invoice_services'));
        $this->assertStringContainsString('"social_security_rate":4', Setting::getValue('invoice_services'));
    }

    public function test_invoice_xml_export_settings_show_filtered_count(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'fiscal_code' => 'RSSMRA80A01H501U',
            'address' => 'Via Roma',
            'street_number' => '1',
            'postal_code' => '61032',
            'city' => 'Fano',
            'province' => 'PU',
            'country_id' => 'IT',
        ]);

        Invoice::create([
            'patient_id' => $patient->id,
            'number' => '1/2026',
            'progressive_number' => 1,
            'issued_at' => '2026-06-01',
            'service' => 'Seduta di manipolazione osteopatica',
            'amount' => '40',
            'status' => 'paid',
        ]);
        Invoice::create([
            'patient_id' => $patient->id,
            'number' => '1/2025',
            'progressive_number' => 1,
            'issued_at' => '2025-06-01',
            'service' => 'Seduta di manipolazione osteopatica',
            'amount' => '40',
            'status' => 'paid',
        ]);

        $this->actingAs($user)
            ->get(route('settings.invoices', [
                'invoice_export_from' => '2026-06-01',
                'invoice_export_to' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('Fatture che saranno esportate')
            ->assertSee('>1<', false)
            ->assertSee('Ultima settimana')
            ->assertSee('Ultimo mese')
            ->assertSee('Tutte');
    }

    public function test_invoices_can_be_exported_as_xml_zip(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'fiscal_code' => 'RSSMRA80A01H501U',
            'address' => 'Via Roma',
            'street_number' => '1',
            'postal_code' => '61032',
            'city' => 'Fano',
            'province' => 'PU',
            'country_id' => 'IT',
            'telematic_address' => '0000000',
        ]);
        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'number' => '3/2026',
            'progressive_number' => 3,
            'issued_at' => '2026-06-01',
            'service' => 'Seduta di manipolazione osteopatica',
            'amount' => '40',
            'status' => 'paid',
        ]);

        $response = $this->actingAs($user)
            ->get(route('settings.invoices.export-xml', [
                'from' => '2026-06-01',
                'to' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/zip');

        $path = tempnam(sys_get_temp_dir(), 'invoice-xml-test-');
        file_put_contents($path, $response->getContent());

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        $this->assertSame(1, $zip->numFiles);
        $xml = $zip->getFromIndex(0);
        $zip->close();
        unlink($path);

        $this->assertStringContainsString('<p:FatturaElettronica', $xml);
        $this->assertStringContainsString('xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2"', $xml);
        $this->assertStringContainsString("<FatturaElettronicaHeader>\n    <DatiTrasmissione>", $xml);
        $this->assertStringContainsString('<Numero>3/2026</Numero>', $xml);
        $this->assertStringContainsString('<ImportoTotaleDocumento>40.00</ImportoTotaleDocumento>', $xml);
        $this->assertNotNull($invoice->fresh()->xml_downloaded_at);
    }

    public function test_invoices_can_be_imported_from_excel_and_linked_to_patient(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'legacy_patient_id' => 672,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        $file = $this->invoiceUpload([
            ['IDFattura', 'N Fattura', 'Data di emissione', 'Cliente', 'Idpaziente', 'Descrizione', 'Importo', 'Inps', 'Bollo', 'Totale'],
            ['2338', '3/2025', '2025-01-08 00:00:00', '', '672', 'Seduta di manipolazione osteopatica', '38.46', '1.54', '0', '40'],
        ]);

        $this->actingAs($user)
            ->post(route('settings.invoices.import'), [
                'invoices_file' => $file,
            ])
            ->assertRedirect(route('settings.invoices'));

        $this->assertDatabaseHas('invoices', [
            'patient_id' => $patient->id,
            'number' => '3/2025',
            'progressive_number' => 3,
            'year' => 2025,
            'service' => 'Seduta di manipolazione osteopatica',
            'amount' => '40.00',
            'status' => 'paid',
        ]);
    }

    public function test_invoice_import_does_not_match_internal_patient_id_when_legacy_archive_exists(): void
    {
        $user = User::factory()->create();
        Patient::create([
            'user_id' => $user->id,
            'legacy_patient_id' => 672,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);
        Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Luisa',
            'last_name' => 'Bianchi',
        ]);

        $file = $this->invoiceUpload([
            ['IDFattura', 'N Fattura', 'Data di emissione', 'Cliente', 'Idpaziente', 'Descrizione', 'Importo', 'Inps', 'Bollo', 'Totale'],
            ['9999', '310/2025', '2025-08-11 00:00:00', '', '2', 'Seduta di manipolazione osteopatica', '38.46', '1.54', '0', '40'],
        ]);

        $this->actingAs($user)
            ->post(route('settings.invoices.import'), [
                'invoices_file' => $file,
            ])
            ->assertRedirect(route('settings.invoices'));

        $this->assertDatabaseMissing('invoices', [
            'number' => '310/2025',
        ]);
    }

    public function test_patient_export_settings_show_filtered_count(): void
    {
        $user = User::factory()->create();
        Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ])->forceFill([
            'created_at' => '2026-06-01 10:00:00',
            'updated_at' => '2026-06-01 10:00:00',
        ])->save();
        Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Luisa',
            'last_name' => 'Bianchi',
        ])->forceFill([
            'created_at' => '2026-05-01 10:00:00',
            'updated_at' => '2026-05-01 10:00:00',
        ])->save();

        $this->actingAs($user)
            ->get(route('settings.patients', [
                'patient_export_from' => '2026-06-01',
                'patient_export_to' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('Impostazioni pazienti')
            ->assertSee('Esporta Excel pazienti')
            ->assertSee('Pazienti che saranno esportati')
            ->assertSee('>1<', false)
            ->assertSee('Ultima settimana')
            ->assertSee('Ultimo mese')
            ->assertSee('Tutti');
    }

    public function test_patient_duplicates_can_be_merged_from_settings(): void
    {
        $user = User::factory()->create();
        $primary = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Simona',
            'last_name' => 'Lucarelli',
            'phone' => '3474218408',
        ]);
        $duplicate = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Simona',
            'last_name' => 'Lucarelli',
            'phone' => '3474218408',
            'email' => 'simona@example.com',
        ]);

        $duplicate->treatmentSessions()->create([
            'session_date' => '2026-06-20',
            'title' => 'Seduta',
            'fee' => 40,
        ]);
        Invoice::create([
            'patient_id' => $duplicate->id,
            'number' => '1/2026',
            'year' => 2026,
            'progressive_number' => 1,
            'issued_at' => '2026-06-20',
            'amount' => 40,
            'status' => 'paid',
        ]);
        Appointment::create([
            'patient_id' => $duplicate->id,
            'title' => 'Lucarelli Simona',
            'starts_at' => '2026-06-20 09:00:00',
            'ends_at' => '2026-06-20 09:45:00',
            'type' => 'visit',
            'status' => 'scheduled',
        ]);

        $this->actingAs($user)
            ->post(route('settings.patients.merge'), [
                'primary_patient_id' => $primary->id,
                'duplicate_patient_ids' => [$duplicate->id],
            ])
            ->assertRedirect(route('settings.patients'));

        $this->assertDatabaseMissing('patients', ['id' => $duplicate->id]);
        $this->assertDatabaseHas('patients', [
            'id' => $primary->id,
            'email' => 'simona@example.com',
        ]);
        $this->assertDatabaseHas('treatment_sessions', ['patient_id' => $primary->id]);
        $this->assertDatabaseHas('invoices', ['patient_id' => $primary->id]);
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $primary->id,
            'patient_match_status' => 'matched',
        ]);
    }

    public function test_backup_can_be_run_from_settings(): void
    {
        $user = User::factory()->create();
        $backupPath = storage_path('framework/testing/backups');

        File::deleteDirectory($backupPath);

        Setting::setValue('backup_path', $backupPath, 'backup');
        Setting::setValue('backup_database', '1', 'backup');
        Setting::setValue('backup_uploaded_files', '0', 'backup');
        Setting::setValue('backup_generated_documents', '0', 'backup');
        Setting::setValue('backup_logs', '0', 'backup');

        $this->actingAs($user)
            ->post(route('settings.backup.run'))
            ->assertRedirect(route('settings.backup'))
            ->assertSessionHas('status');

        $files = File::files($backupPath);

        $this->assertCount(1, $files);
        $this->assertSame('zip', $files[0]->getExtension());

        File::deleteDirectory($backupPath);
    }

    public function test_backup_can_be_restored_from_settings(): void
    {
        $user = User::factory()->create();
        $backupPath = storage_path('framework/testing/restore-backups');

        File::deleteDirectory($backupPath);

        Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        Setting::setValue('backup_path', $backupPath, 'backup');
        Setting::setValue('backup_database', '1', 'backup');
        Setting::setValue('backup_uploaded_files', '0', 'backup');
        Setting::setValue('backup_generated_documents', '0', 'backup');
        Setting::setValue('backup_logs', '0', 'backup');

        $backup = ApplicationBackup::run();

        Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Luisa',
            'last_name' => 'Bianchi',
        ]);

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Luisa',
            'last_name' => 'Bianchi',
        ]);

        $upload = new UploadedFile($backup['path'], $backup['filename'], 'application/zip', null, true);

        $this->actingAs($user)
            ->post(route('settings.backup.restore'), [
                'backup_file' => $upload,
                'restore_database' => '1',
                'restore_confirmation' => 'RIPRISTINA',
            ])
            ->assertRedirect(route('settings.backup'));

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);
        $this->assertDatabaseMissing('patients', [
            'first_name' => 'Luisa',
            'last_name' => 'Bianchi',
        ]);

        File::deleteDirectory($backupPath);
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

    private function invoiceUpload(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'invoice-import-');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Fatture" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->invoiceWorksheet($rows));
        $zip->close();

        return new UploadedFile($path, 'Fatture.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function invoiceWorksheet(array $rows): string
    {
        $xmlRows = [];
        foreach ($rows as $rowIndex => $row) {
            $cells = [];
            foreach ($row as $columnIndex => $value) {
                $cell = $this->columnName($columnIndex + 1).($rowIndex + 1);
                $cells[] = '<c r="'.$cell.'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</t></is></c>';
            }
            $xmlRows[] = '<row r="'.($rowIndex + 1).'">'.implode('', $cells).'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.implode('', $xmlRows).'</sheetData></worksheet>';
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }
}
