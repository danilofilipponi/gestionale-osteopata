<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('patients', PatientController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/patients/{patient}/medical-record', [PatientController::class, 'storeMedicalRecord'])
        ->name('patients.medical-record.store');
    Route::post('/patients/{patient}/sessions', [PatientController::class, 'storeTreatmentSession'])
        ->name('patients.sessions.store');
    Route::post('/patients/{patient}/invoices', [PatientController::class, 'storeInvoice'])
        ->name('patients.invoices.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
