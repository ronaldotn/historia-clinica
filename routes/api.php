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
Route::post('/patients', [PatientController::class, 'store']);
// Route::get('/patients/{uuid}', [PatientController::class, 'show']);
// Route::get('/Patient?identifier={value}', [PatientController::class, 'showByidentifier']); 
// Route::get('/Patient?name={value}', [PatientController::class, 'showByname']);
// Route::get('/Patient?birthdate={date} ', [PatientController::class, 'showBybirthdate']);
// Route::get('/Patient?phone={number}', [PatientController::class, 'showByphone']);
// Route::get('/Patient?address-city={city}', [PatientController::class, 'showByaddresscity']);
// Route::get('/Patient?_text={value}', [PatientController::class, 'showBytextsearch']);
// Route::get('/Patient?_count={n}&_offset={n}', [PatientController::class, 'showBypaginated']);


