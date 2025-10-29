<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\PractitionerController;
use App\Http\Controllers\API\PatientController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['auth:sanctum', 'role:admin|rrhh'])->group(function () {
    Route::apiResource('practitioners', PractitionerController::class);

    // Rutas para la gestión de pacientes duplicados
    Route::get('patients/duplicates', [PatientController::class, 'duplicates']);
    Route::post('patients/merge', [PatientController::class, 'mergeDuplicates']);
});

// Aquí puedes añadir el resto de tus rutas de pacientes, como Route::apiResource('patients', PatientController::class);
