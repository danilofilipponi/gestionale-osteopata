<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/settings/patients/export', [PatientController::class, 'export'])->name('patients.export');
    Route::post('/settings/patients/import', [PatientController::class, 'import'])->name('patients.import');
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::resource('patients', PatientController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::get('/patients/{patient}/anamnesis', [PatientController::class, 'anamnesis'])->name('patients.anamnesis.index');
    Route::get('/patients/{patient}/sessions', [PatientController::class, 'sessions'])->name('patients.sessions.index');
    Route::get('/patients/{patient}/invoices', [PatientController::class, 'invoices'])->name('patients.invoices.index');
    Route::get('/patients/{patient}/privacy', [PatientController::class, 'privacy'])->name('patients.privacy.index');
    Route::resource('appointments', AppointmentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('/appointments/{appointment}/move', [AppointmentController::class, 'move'])->name('appointments.move');
    Route::patch('/appointments/{appointment}/patient-match', [AppointmentController::class, 'resolvePatient'])->name('appointments.patient-match.resolve');
    Route::post('/appointments/{appointment}/patient-match/ignore', [AppointmentController::class, 'ignorePatientMatch'])->name('appointments.patient-match.ignore');
    Route::post('/appointments/{appointment}/patient-match/new-patient', [AppointmentController::class, 'createPatientFromMatch'])->name('appointments.patient-match.new-patient');
    Route::post('/patients/{patient}/medical-record', [PatientController::class, 'storeMedicalRecord'])
        ->name('patients.medical-record.store');
    Route::post('/patients/{patient}/privacy-consent', [PatientController::class, 'storePrivacyConsent'])
        ->name('patients.privacy-consent.store');
    Route::get('/patients/{patient}/privacy-consent/pdf', [PatientController::class, 'privacyConsentPdf'])
        ->name('patients.privacy-consent.pdf');
    Route::post('/patients/{patient}/sessions', [PatientController::class, 'storeTreatmentSession'])
        ->name('patients.sessions.store');
    Route::patch('/patients/{patient}/sessions/{session}', [PatientController::class, 'updateTreatmentSession'])
        ->name('patients.sessions.update');
    Route::delete('/patients/{patient}/sessions/{session}', [PatientController::class, 'destroyTreatmentSession'])
        ->name('patients.sessions.destroy');
    Route::post('/patients/{patient}/invoices', [PatientController::class, 'storeInvoice'])
        ->name('patients.invoices.store');
    Route::get('/patients/{patient}/invoices/{invoice}/preview', [PatientController::class, 'previewInvoice'])
        ->name('patients.invoices.preview');
    Route::get('/patients/{patient}/invoices/{invoice}/pdf', [PatientController::class, 'pdfInvoice'])
        ->name('patients.invoices.pdf');
    Route::patch('/patients/{patient}/invoices/{invoice}', [PatientController::class, 'updateInvoice'])
        ->name('patients.invoices.update');
    Route::delete('/patients/{patient}/invoices/{invoice}', [PatientController::class, 'destroyInvoice'])
        ->name('patients.invoices.destroy');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::get('/settings/patients', [SettingsController::class, 'patients'])->name('settings.patients');
    Route::get('/settings/users', [SettingsController::class, 'users'])->name('settings.users');
    Route::get('/settings/invoices', [SettingsController::class, 'invoices'])->name('settings.invoices');
    Route::get('/settings/sessions', [SettingsController::class, 'sessions'])->name('settings.sessions');
    Route::get('/settings/agenda', [SettingsController::class, 'agenda'])->name('settings.agenda');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::patch('/settings/invoices', [SettingsController::class, 'updateInvoices'])->name('settings.invoices.update');
    Route::patch('/settings/sessions', [SettingsController::class, 'updateSessions'])->name('settings.sessions.update');
    Route::patch('/settings/agenda', [SettingsController::class, 'updateAgenda'])->name('settings.agenda.update');
    Route::get('/settings/invoices/export-xml', [SettingsController::class, 'exportInvoicesXml'])->name('settings.invoices.export-xml');
    Route::post('/settings/invoices/import', [SettingsController::class, 'importInvoices'])->name('settings.invoices.import');
    Route::post('/settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
    Route::patch('/settings/users/{user}', [SettingsController::class, 'updateUser'])->name('settings.users.update');
    Route::delete('/settings/users/{user}', [SettingsController::class, 'destroyUser'])->name('settings.users.destroy');

    Route::get('/google/calendar/connect', [GoogleCalendarController::class, 'connect'])->name('google.calendar.connect');
    Route::get('/google/calendar/callback', [GoogleCalendarController::class, 'callback'])->name('google.calendar.callback');
    Route::post('/google/calendar/disconnect', [GoogleCalendarController::class, 'disconnect'])->name('google.calendar.disconnect');
    Route::post('/google/calendar/sync', [GoogleCalendarController::class, 'sync'])->name('google.calendar.sync');
    Route::post('/google/calendar/auto-sync', [GoogleCalendarController::class, 'autoSync'])->name('google.calendar.auto-sync');
    Route::post('/google/calendar/calendars', [GoogleCalendarController::class, 'refreshCalendars'])->name('google.calendar.calendars');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
