<?php

namespace App\Http\Controllers\API;

use App\Models\Patient;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Validation\ValidationException;

class PatientController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */

   public function store(Request $request)
    {
        // Paso 1: Validar los datos de entrada
        try {
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255',
                'apellidos' => 'required|string|max:255',
                'documento_identidad' => 'required|string|max:255|unique:patients,documento_identidad',
                'fecha_nacimiento' => 'required|date',
                'sexo' => 'required|string|in:masculino,femenino,otro',
                'direccion' => 'nullable|string|max:255',
                'contacto' => 'nullable|string|max:255',
                'correo' => 'required|email|max:255|unique:patients,correo',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422); // Código 422 Unprocessable Entity
        }

        // Paso 2: Validación de duplicados con reglas adicionales
        $existingPatient = Patient::where('documento_identidad', $validatedData['documento_identidad'])
            ->orWhere(function ($query) use ($validatedData) {
                $query->where('nombre', $validatedData['nombre'])
                      ->where('apellidos', $validatedData['apellidos'])
                      ->where('fecha_nacimiento', $validatedData['fecha_nacimiento']);
            })->first();

        if ($existingPatient) {
            return response()->json([
                'message' => 'El paciente ya existe en el sistema.',
                'patient_uuid' => $existingPatient->uuid
            ], 409); // Código 409 Conflict
        }

        // Paso 3: Crear el identificador FHIR
        $fhirIdentifier = [
            'system' => 'http://hospital-bolivia.gob.bo/sid/patient-identifier',
            'value' => Str::uuid()->toString(), // Usamos un UUID para el valor
            'type' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                        'code' => 'MR',
                        'display' => 'Medical Record Number'
                    ]
                ]
            ]
        ];

        // Paso 4: Persistir en la base de datos con Eloquent
        $patient = Patient::create(array_merge($validatedData, [
            'fhir_identifier' => $fhirIdentifier
        ]));

        // Paso 5: Devolver una respuesta JSON exitosa
        return response()->json([
            'message' => 'Paciente registrado exitosamente',
            'data' => $patient
        ], 201); // Código 201 Created
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
         
        $patient = Patient::where('uuid', $uuid)->first();
// dd($patient);
        if (!$patient) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }

        return response()->json([
            'message' => 'Paciente encontrado',
            'data' => $patient
        ], 200);

    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Patient $patient)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient)
    {
        //
    }
}
