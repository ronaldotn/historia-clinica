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

    // public function duplicates()
    // {
    //     // Encontrar grupos duplicados por nombre + apellido + fecha de nacimiento
    //     $groups = DB::table('patients')
    //         ->select(
    //             'first_name',
    //             'last_name',
    //             'date_of_birth',
    //             DB::raw('COUNT(*) as total')
    //         )
    //         ->groupBy('first_name', 'last_name', 'date_of_birth')
    //         ->having('total', '>', 1)
    //         ->get();

    //     $result = [];

    //     // Por cada grupo duplicado → obtener los pacientes reales
    //     foreach ($groups as $group) {
    //         $patients = Patient::where('first_name', $group->first_name)
    //             ->where('last_name', $group->last_name)
    //             ->where('date_of_birth', $group->date_of_birth)
    //             ->get();

    //         $result[] = [
    //             'first_name'      => $group->first_name,
    //             'last_name'       => $group->last_name,
    //             'date_of_birth'   => $group->date_of_birth,
    //             'total'           => $group->total,
    //             'patients'        => $patients
    //         ];
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $result,
    //     ]);
    // }

    public function duplicates()
    {
        try {
            // Obtengo todos los pacientes
            $patients = Patient::all();

            if ($patients->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No hay pacientes registrados.',
                    'data' => []
                ], 200);
            }

            // Grafo: id => lista de vecinos
            $graph = [];
            // Scores por pares
            $scores = [];
            
            foreach ($patients as $p) {
                $graph[$p->id] = [];
            }

            // Compara total (n^2 / 2)
            foreach ($patients as $i => $a) {
                for ($j = $i + 1; $j < count($patients); $j++) {
                    $b = $patients[$j];

                    $score = $this->calculateScore($a, $b);

                    // Guardar score entre A y B
                    $scores[$a->id][$b->id] = $score;
                    $scores[$b->id][$a->id] = $score;

                    if ($score >= 70) { 
                        // 70 o más = duplicado probable
                        $graph[$a->id][] = $b->id;
                        $graph[$b->id][] = $a->id;
                    }
                }
            }

            // BFS para formar grupos
            $visited = [];
            $groups = [];
            $groupId = 1;

            foreach ($patients as $p) {
                if (isset($visited[$p->id])) continue;

                $queue = [$p->id];
                $visited[$p->id] = true;
                $members = [];

                while (!empty($queue)) {
                    $node = array_shift($queue);
                    $members[] = $node;

                    foreach ($graph[$node] as $neighbor) {
                        if (!isset($visited[$neighbor])) {
                            $visited[$neighbor] = true;
                            $queue[] = $neighbor;
                        }
                    }
                }

                if (count($members) <= 1) continue;

                // Calcular estadísticos del grupo
                $pairScores = [];
                for ($i = 0; $i < count($members); $i++) {
                    for ($j = $i + 1; $j < count($members); $j++) {
                        $a = $members[$i];
                        $b = $members[$j];
                        $pairScores[] = $scores[$a][$b];
                    }
                }

                $groups[] = [
                    "group_id" => $groupId++,
                    "size" => count($members),
                    "average_score" => round(array_sum($pairScores) / count($pairScores), 2),
                    "max_score" => max($pairScores),
                    "min_score" => min($pairScores),
                    "pair_scores" => $this->detailedPairScores($members, $scores),
                    "members" => Patient::whereIn('id', $members)->get()
                ];
            }

            return response()->json([
                "status" => "success",
                "message" => "Grupos de posibles duplicados detectados.",
                "groups" => $groups
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                "status" => "error",
                "message" => "Error interno.",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    private function calculateScore($a, $b)
    {
        $score = 0;

        // Coincidencia exacta de CI
        if (!empty($a->identifier) && $a->identifier === $b->identifier) {
            return 100;
        }

        // Similitud en nombre
        similar_text(strtolower($a->first_name), strtolower($b->first_name), $namePercent);

        // Similitud en apellido
        similar_text(strtolower($a->last_name), strtolower($b->last_name), $lastPercent);

        $score = max($namePercent, $lastPercent);

        return round($score, 2);
    }

    private function detailedPairScores($members, $scores)
    {
        $result = [];
        for ($i = 0; $i < count($members); $i++) {
            for ($j = $i + 1; $j < count($members); $j++) {
                $a = $members[$i];
                $b = $members[$j];

                $result[] = [
                    "patient_a_id" => $a,
                    "patient_b_id" => $b,
                    "score" => $scores[$a][$b]
                ];
            }
        }
        return $result;
    }

    public function merge(Request $request)
    {
        $request->validate([
            "main_id" => "required|exists:patients,id",
            "merge_ids" => "required|array|min:1",
            "merge_ids.*" => "exists:patients,id"
        ]);

        $mainId = $request->main_id;
        $mergeIds = $request->merge_ids;

        // No permitir que el principal esté dentro de merge_ids
        if (in_array($mainId, $mergeIds)) {
            return response()->json([
                "status" => "error",
                "message" => "El paciente principal no puede estar en la lista de fusión."
            ], 400);
        }

        // Obtener al paciente principal
        $main = Patient::findOrFail($mainId);

        // Obtener los duplicados
        $patientsToMerge = Patient::whereIn("id", $mergeIds)->get();

        DB::beginTransaction();
        try {

            foreach ($patientsToMerge as $p) {

                // Mover relaciones al principal
                $p->encounters()->update(["patient_id" => $mainId]);
                $p->conditions()->update(["patient_id" => $mainId]);
                $p->observations()->update(["patient_id" => $mainId]);
                $p->diagnosticReports()->update(["patient_id" => $mainId]);
                $p->consents()->update(["patient_id" => $mainId]);

                // Fusionar campos (solo si main está vacío)
                $main->first_name     ??= $p->first_name;
                $main->last_name      ??= $p->last_name;
                $main->date_of_birth  ??= $p->date_of_birth;
                $main->gender         ??= $p->gender;
                $main->phone          ??= $p->phone;
                $main->email          ??= $p->email;
                $main->address        ??= $p->address;

                // Eliminar al duplicado
                $p->delete();
            }

            // Guardar la fusión final
            $main->save();

            DB::commit();

            return response()->json([
                "status" => "success",
                "message" => "Pacientes fusionados correctamente.",
                "main_patient" => $main
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                "status" => "error",
                "message" => "Error al fusionar",
                "error" => $e->getMessage()
            ], 500);
        }
    }


}
