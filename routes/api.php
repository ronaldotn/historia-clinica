<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PatientController;
use App\Http\Controllers\API\PractitionerController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me-roles', function () {
        $u = auth()->user();
        return response()->json([
            'id' => $u->id,
            'name' => $u->name,
            'roles' => $u->getRoleNames(),
        ]);
    });
    //ralimit, franz

    
        Route::middleware(['role:admin|rrhh'])->group(function () {
        // Lectura
        Route::get('/practitioners', [PractitionerController::class, 'index']);
        Route::get('/practitioners/{practitioner}', [PractitionerController::class, 'show']);

        // Escritura
        Route::post('/practitioners', [PractitionerController::class, 'store']);
        Route::put('/practitioners/{practitioner}', [PractitionerController::class, 'update']);
        Route::delete('/practitioners/{practitioner}', [PractitionerController::class, 'destroy']);
    });
});
// =====================
// Pacientes existentes
// =====================
Route::get('/patients', [PatientController::class, 'index']);
Route::get('/metrics', [PatientController::class, 'metrics']);
Route::get('/patients/{uuid}', [PatientController::class, 'show']);
Route::post('/patients', [PatientController::class, 'store']);
Route::put('/patients/{uuid}', [PatientController::class, 'update']);
Route::delete('/patients/{uuid}', [PatientController::class, 'destroy']);

// =====================
// Duplicados
// =====================

// 🔹 Detectar duplicados potenciales
Route::get('/patients/duplicates', [PatientController::class, 'duplicates']);

// 🔹 Fusionar duplicados (solo admin)
Route::post('/patients/merge', [PatientController::class, 'mergeDuplicates']);
