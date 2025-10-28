<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PatientController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    //ralimit, franz
});
<<<<<<< HEAD
Route::post('/patients', [PatientController::class, 'store']);
// Route::get('/patients/{uuid}', [PatientController::class, 'show']);
// Route::get('/Patient?identifier={value}', [PatientController::class, 'showByidentifier']); 
// Route::get('/Patient?name={value}', [PatientController::class, 'showByname']);
// Route::get('/Patient?birthdate={date} ', [PatientController::class, 'showBybirthdate']);
// Route::get('/Patient?phone={number}', [PatientController::class, 'showByphone']);
// Route::get('/Patient?address-city={city}', [PatientController::class, 'showByaddresscity']);
// Route::get('/Patient?_text={value}', [PatientController::class, 'showBytextsearch']);
// Route::get('/Patient?_count={n}&_offset={n}', [PatientController::class, 'showBypaginated']);


=======
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
>>>>>>> dc67e1d51126455957939f8dfb0a330be000a05b
