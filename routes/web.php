<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AppointmentController;
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
    Route::resource('patients', PatientController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::get('/patients/{patient}/sessions', [PatientController::class, 'sessions'])->name('patients.sessions.index');
    Route::get('/patients/{patient}/invoices', [PatientController::class, 'invoices'])->name('patients.invoices.index');
    Route::get('/patients/{patient}/privacy', [PatientController::class, 'privacy'])->name('patients.privacy.index');
    Route::resource('appointments', AppointmentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/patients/{patient}/medical-record', [PatientController::class, 'storeMedicalRecord'])
        ->name('patients.medical-record.store');
    Route::post('/patients/{patient}/privacy-consent', [PatientController::class, 'storePrivacyConsent'])
        ->name('patients.privacy-consent.store');
    Route::post('/patients/{patient}/sessions', [PatientController::class, 'storeTreatmentSession'])
        ->name('patients.sessions.store');
    Route::patch('/patients/{patient}/sessions/{session}', [PatientController::class, 'updateTreatmentSession'])
        ->name('patients.sessions.update');
    Route::delete('/patients/{patient}/sessions/{session}', [PatientController::class, 'destroyTreatmentSession'])
        ->name('patients.sessions.destroy');
    Route::post('/patients/{patient}/invoices', [PatientController::class, 'storeInvoice'])
        ->name('patients.invoices.store');
    Route::patch('/patients/{patient}/invoices/{invoice}', [PatientController::class, 'updateInvoice'])
        ->name('patients.invoices.update');
    Route::delete('/patients/{patient}/invoices/{invoice}', [PatientController::class, 'destroyInvoice'])
        ->name('patients.invoices.destroy');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
    Route::patch('/settings/users/{user}', [SettingsController::class, 'updateUser'])->name('settings.users.update');
    Route::delete('/settings/users/{user}', [SettingsController::class, 'destroyUser'])->name('settings.users.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
