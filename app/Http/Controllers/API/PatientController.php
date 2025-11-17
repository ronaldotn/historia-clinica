<?php

namespace App\Http\Controllers\API;

use App\Models\Patient;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class PatientController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            "message" => "Lista de pacientes",
            "data"    => Patient::paginate(10)
        ], 200);
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
    public function show($id)
    {
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json([
                'message' => 'Paciente no encontrado'
            ], 404);
        }

        return response()->json([
            'message' => 'Paciente encontrado',
            'data' => $patient
        ],201);

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

    public function duplicates()
    {
        // 1️⃣ Encontrar grupos duplicados por nombre + apellido + fecha de nacimiento
        $groups = DB::table('patients')
            ->select(
                'first_name',
                'last_name',
                'date_of_birth',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('first_name', 'last_name', 'date_of_birth')
            ->having('total', '>', 1)
            ->get();

        $result = [];

        // 2️⃣ Por cada grupo duplicado → obtener los pacientes reales
        foreach ($groups as $group) {
            $patients = Patient::where('first_name', $group->first_name)
                ->where('last_name', $group->last_name)
                ->where('date_of_birth', $group->date_of_birth)
                ->get();

            $result[] = [
                'first_name'      => $group->first_name,
                'last_name'       => $group->last_name,
                'date_of_birth'   => $group->date_of_birth,
                'total'           => $group->total,
                'patients'        => $patients
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
