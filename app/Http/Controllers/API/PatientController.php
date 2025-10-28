<?php

namespace App\Http\Controllers\API;


use App\Models\Patient;
use App\Models\AuditEvents;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\BaseController;

class PatientController extends BaseController
{
    /**
     * Listar todos los pacientes
     */
    public function index(Request $request): JsonResponse
    {
        HEAD
        // hola
        
        // ===========================
        // 🧭 1️⃣ Parámetros de entrada
        // ===========================
        $firstName   = trim($request->input('first_name', ''));  // Nombre
        $lastName    = trim($request->input('last_name', ''));   // Apellido
        $identifier  = trim($request->input('identifier', ''));  // Documento
        $birthdate   = trim($request->input('birthdate', ''));   // Fecha de nacimiento
        $phone       = trim($request->input('phone', ''));       // Teléfono
        $address     = trim($request->input('address', ''));     // Dirección
        $count      = (int) ($request->_count ?? 10);            // Registros por página

        // ======================================
        // 🔹 1️⃣ Construir query base
        // ======================================
        $query = Patient::query();

        // ===========================
        // 🧮 2️⃣ Construcción dinámica de la consulta
        // ===========================
        $query = Patient::query();

        // Filtrar por nombre (LIKE para coincidencias parciales)
        if (!empty($firstName)) {
            $query->where('first_name', 'LIKE', "%{$firstName}%");
        }

        // Filtrar por apellido
        if (!empty($lastName)) {
            $query->where('last_name', 'LIKE', "%{$lastName}%");
        }

        // Filtrar por documento exacto
        if (!empty($identifier)) {
            $query->where('identifier', $identifier);
        }

        // Filtrar por fecha de nacimiento exacta
        if (!empty($birthdate)) {
            $query->whereDate('date_of_birth', $birthdate);
        }

        // Filtrar por teléfono
        if (!empty($phone)) {
            $query->where('phone', 'LIKE', "%{$phone}%");
        }

        // Filtrar por dirección (parcial)
        if (!empty($address)) {
            $query->where('address', 'LIKE', "%{$address}%");
        }

        // Filtros faceted
        if ($request->filled('is_active')) {
            $query->whereNotNull('email'); // ejemplo
        }
        if ($ageRange = $request->input('ageRange')) {
            [$min, $max] = explode('-', $ageRange);
            $query->whereBetween('date_of_birth', [now()->subYears($max), now()->subYears($min)]);
        }

        // Ordenamiento
        $query->orderBy('created_at', 'desc');


        // ======================================
        // 🔹 4️⃣ Paginación automática
        // ======================================
        // paginate() maneja automáticamente offset, total, páginas
        $patients = $query->paginate($count);

        // ======================================
        // 🔹 5️⃣ Devolver respuesta JSON
        // ======================================
        return $this->sendResponse($patients, 'Lista de pacientes filtrada y ordenada');
    }


    /**
     * 🔹 Registrar nuevo paciente (FHIR Compatible)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier'     => ['required', 'unique:patients,identifier'],
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'date_of_birth'  => 'required|date|before:today',
            'gender'         => 'required|in:male,female,other,unknown',
            'phone'          => 'nullable',
            'email'          => 'nullable|email|max:100',
            'address'        => 'nullable|string|max:255',
        ]);

        // 🔍 Verificación adicional de duplicados
        $duplicate = Patient::where('first_name', $validated['first_name'])
            ->where('last_name', $validated['last_name'])
            ->where('date_of_birth', $validated['date_of_birth'])
            ->first();

        if ($duplicate) {
            return $this->sendError(
                'Ya existe un paciente con el mismo nombre y fecha de nacimiento.',
                409,
                ['existing' => $duplicate]
            );
        }

        // Crear paciente
        $patient = Patient::create($validated);

        // 🕵️‍♂️ Auditoría (seguridad y trazabilidad)
        AuditEvents::create([
            'user_id'   => Auth::id(),
            'action'    => 'create',
            'resource'  => 'Patient/' . $patient->id,
            'timestamp' => now(),
            'details'   => [
                'created_by' => Auth::user()->name ?? 'System',
                'timestamp'  => now()->toIso8601String(), // ISO8601
                // 'ip'         => $request->ip(),
                // 'user_agent' => $request->header('User-Agent'),
            ],
        ]);

        return $this->sendResponse($patient, 'Paciente creado exitosamente.', 201);

    }

    /**
     * Mostrar un paciente específico
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
        ], 200);
    }

    /**
     * Actualizar un paciente existente
     */
    public function update(Request $request, $id)
    {
        // Buscar paciente por id
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json([
                'message' => 'Paciente no encontrado'
            ], 404);
        }

        // Validación de campos que pueden actualizarse
        $validated = $request->validate([
            'identifier' => 'sometimes|required|unique:patients,identifier,' . $id,
            'first_name' => 'sometimes|required|string|max:100',
            'last_name'  => 'sometimes|required|string|max:100',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other,unknown',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
        ]);

        // Actualizar paciente
        $patient->update($validated);

        // Respuesta JSON
        return response()->json([
            'message' => 'Paciente actualizado exitosamente',
            'data' => $patient
        ], 200);
    }

    /**
     * Eliminar un paciente
     */
    public function destroy($id)
    {
        // Buscar paciente por id
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json([
                'message' => 'Paciente no encontrado'
            ], 404);
        }

        // Eliminar paciente
        $patient->delete();

        // Respuesta JSON
        return response()->json([
            'message' => 'Paciente eliminado exitosamente'
        ], 200);
    }
    /**
     * 🔹 Obtener métricas generales de pacientes
     */
    public function metrics(): JsonResponse
    {
        // 🔹 Total de pacientes
        $totalPatients = Patient::count();

        // 🔹 Pacientes que tienen al menos un encounter (consultas/hospitalizaciones)
        $patientsWithEncounters = Patient::has('encounters')->count();

        // 🔹 Pacientes que tienen alguna condición registrada
        $patientsWithConditions = Patient::has('conditions')->count();

        // 🔹 Pacientes con observaciones (ej. signos vitales o laboratorios)
        $patientsWithObservations = Patient::has('observations')->count();

        return $this->sendResponse([
            'totalPatients'            => $totalPatients,
            'patientsWithEncounters'   => $patientsWithEncounters,
            'patientsWithConditions'   => $patientsWithConditions,
            'patientsWithObservations' => $patientsWithObservations,
        ], 'Métricas de pacientes obtenidas');
    }
    /**
     * 🔹 Detectar duplicados potenciales
     * Endpoint: GET /api/patients/duplicates
     * Query Params:
     * - search (opcional): nombre, apellido o documento para filtrar
     */
    public function duplicates(Request $request): JsonResponse
    {
        $search = trim($request->input('search', ''));

        // Base query
        $query = Patient::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('identifier', 'LIKE', "%{$search}%");
            });
        }

        // Traemos los pacientes relevantes (limit opcional, paginación si quieres)
        $patients = $query->orderBy('created_at', 'desc')->get();

        // Lista para almacenar duplicados
        $duplicates = collect();

        // Eloquent Matching probabilístico simplificado
        $patients->each(function ($p, $i) use ($patients, $duplicates) {
            $patients->slice($i + 1)->each(function ($other) use ($p, $duplicates) {
                $score = 0;

                // Documento exacto
                if ($p->identifier && $other->identifier && $p->identifier === $other->identifier) {
                    $score += 50;
                }

                // Nombre + apellido similar
                if ($p->first_name && $p->last_name && $other->first_name && $other->last_name) {
                    similar_text(strtolower($p->first_name), strtolower($other->first_name), $percentFirst);
                    similar_text(strtolower($p->last_name), strtolower($other->last_name), $percentLast);
                    $score += (($percentFirst + $percentLast) / 2) * 0.5; // Max 50
                }

                // Fecha de nacimiento exacta
                if ($p->date_of_birth && $p->date_of_birth === $other->date_of_birth) {
                    $score += 20;
                }

                if ($score >= 50) { // Threshold configurable
                    $duplicates->push([
                        'patient_a' => $p,
                        'patient_b' => $other,
                        'score' => round($score, 2)
                    ]);
                }
            });
        });

        return $this->sendResponse(
            $duplicates->sortByDesc('score')->values(),
            'Duplicados potenciales encontrados'
        );
    }

    /**
     * 🔹 Fusionar pacientes duplicados
     * Endpoint: POST /api/patients/merge
     * Params: master_id, merge_ids[]
     */
    public function mergeDuplicates(Request $request): JsonResponse
    {
        $request->validate([
            'master_id' => 'required|exists:patients,id',
            'merge_ids' => 'required|array|min:1',
            'merge_ids.*' => 'exists:patients,id',
        ]);

        $master = Patient::findOrFail($request->master_id);
        $toMerge = Patient::whereIn('id', $request->merge_ids)->get();

        foreach ($toMerge as $patient) {
            // Ejemplo simple: si master tiene campo vacío, toma del duplicado
            foreach ($patient->getAttributes() as $key => $value) {
                if (!$master->$key && $value) {
                    $master->$key = $value;
                }
            }

            // Opcional: transferir relaciones (encounters, condiciones, observaciones)
            // $patient->encounters()->update(['patient_id' => $master->id]);
            // $patient->conditions()->update(['patient_id' => $master->id]);
            // $patient->observations()->update(['patient_id' => $master->id]);

            $patient->delete();
        }

        $master->save();

        return $this->sendResponse($master, 'Pacientes fusionados correctamente');
    }
   // RUTA: GET /Patient?identifier={value} (showByIdentifier)
    /**
     * Search for a Patient by an identifier.
     */
    public function showByIdentifier(Request $request)
    {
        $identifier = $request->get('identifier');

        if (!$identifier) {
             // Podríamos redirigir a una búsqueda más general o dar error 400
             return response()->json(['message' => 'Parámetro "identifier" es requerido.'], 400); 
        }

        $patient = Patient::where('identifier', $identifier)->first();
        
        if (!$patient) {
            return response()->json(['message' => 'Paciente no encontrado con ese identificador'], 404);
        }

        return response()->json($patient);
    }

    // RUTA: GET /Patient?name={value} (showByName)
    /**
     * Search for Patients by name.
     */
    public function showByName(Request $request)
    {
        $name = $request->get('name');
        
        $patients = Patient::where('name', 'LIKE', '%' . $name . '%')
                           ->get(); // Usamos get() porque esperamos una colección

        if ($patients->isEmpty()) {
            return response()->json(['message' => 'No se encontraron pacientes con ese nombre'], 404);
        }

        return response()->json($patients);
    }
    
    // RUTA: GET /Patient?birthdate={date} (showByBirthdate)
    /**
     * Search for Patients by birthdate.
     */
    public function showByBirthdate(Request $request)
    {
        $date = $request->get('birthdate');
        
        $patients = Patient::whereDate('birthdate', $date)->get(); 

        return response()->json($patients); // Devolvemos [] si está vacío
    }
    
    // RUTA: GET /Patient?phone={number} (showByPhone)
    /**
     * Search for a Patient by phone number.
     */
    public function showByPhone(Request $request)
    {
        $number = $request->get('phone');
        
        $patient = Patient::where('phone', $number)->first();
        
        if (!$patient) {
            return response()->json(['message' => 'Paciente no encontrado con ese teléfono'], 404);
        }

        return response()->json($patient);
    }

    // RUTA: GET /Patient?address-city={city} (showByAddressCity)
    /**
     * Search for Patients by address city.
     */
    public function showByAddressCity(Request $request)
    {
        $city = $request->get('address-city');
        
        $patients = Patient::where('address_city', $city)->get(); 

        return response()->json($patients);
    }
    
    // RUTA: GET /Patient?text={value} (showByTextSearch)
    /**
     * Perform a general text search across multiple Patient fields.
     */
    public function showByTextSearch(Request $request)
    {
        $value = $request->get('text');
        
        // Aquí usamos where para buscar en múltiples columnas
        $patients = Patient::where('name', 'LIKE', '%' . $value . '%')
                           ->orWhere('identifier', 'LIKE', '%' . $value . '%')
                           ->orWhere('phone', 'LIKE', '%' . $value . '%')
                           // ... puedes añadir más campos relevantes
                           ->get();

        return response()->json($patients);
    }
    
    // RUTA: GET /Patient?count={n}&offset={n} (indexPaginated)
    /**
     * Get a paginated list of Patients.
     */
    public function indexPaginated(Request $request)
    {
        // Definimos valores por defecto por si no vienen en la URL
        $count = $request->get('count', 15);  // Limit
        $offset = $request->get('offset', 0); // Offset

        $patients = Patient::limit($count)
                           ->offset($offset)
                           ->get();

        // Para escalabilidad, también deberíamos devolver el total de registros:
        $total = Patient::count();

        return response()->json([
            'data' => $patients,
            'meta' => [
                'total' => $total,
                'count' => (int)$count,
                'offset' => (int)$offset
            ]
        ]);
    }

    // Nota: Aunque no lo tienes en tus rutas, el método 'index' es común para listar todos.
    // public function index() { ... }

     public function search(Request $request)
    {
        $query = Patient::query();

        // Filtros básicos
        if ($request->filled('identifier')) {
            $query->where('identifier', $request->identifier);
        }
        if ($request->filled('name')) {
            $tokens = preg_split('/\s+/', $request->name);
            $query->where(function($q) use ($tokens) {
                foreach ($tokens as $token) {
                    $q->orWhere('first_name', 'like', "%$token%")
                      ->orWhere('last_name', 'like', "%$token%") ;
                }
            });
        }
        if ($request->filled('birthdate')) {
            $query->whereDate('birthdate', $request->birthdate);
        }
        if ($request->filled('phone')) {
            $query->where('phone', $request->phone);
        }
        if ($request->filled('address-city')) {
            $query->where('address_city', $request->get('address-city'));
        }
        if ($request->filled('_text')) {
            $text = $request->_text;
            $query->where(function($q) use ($text) {
                $q->orWhere('first_name', 'like', "%$text%")
                  ->orWhere('last_name', 'like', "%$text%")
                  ->orWhere('identifier', 'like', "%$text%")
                  ->orWhere('phone', 'like', "%$text%")
                  ->orWhere('address_city', 'like', "%$text%") ;
            });
        }
        // Faceted search
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        if ($request->filled('organization')) {
            $query->where('organization_id', $request->organization);
        }
        // Rango de edad
        if ($request->filled('age_min') || $request->filled('age_max')) {
            $now = now();
            if ($request->filled('age_min')) {
                $maxBirth = $now->copy()->subYears($request->age_min);
                $query->where('birthdate', '<=', $maxBirth);
            }
            if ($request->filled('age_max')) {
                $minBirth = $now->copy()->subYears($request->age_max + 1)->addDay();
                $query->where('birthdate', '>=', $minBirth);
            }
        }
        // Rango de fechas de registro
        if ($request->filled('registered_from')) {
            $query->where('registered_at', '>=', $request->registered_from);
        }
        if ($request->filled('registered_to')) {
            $query->where('registered_at', '<=', $request->registered_to);
        }
        // Búsqueda geográfica
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }
        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }
        if ($request->filled('municipality')) {
            $query->where('municipality', $request->municipality);
        }
        // Paginación y ordenamiento
        $count = $request->input('_count', 20);
        $offset = $request->input('_offset', 0);
        $query->orderBy('last_name')->orderBy('first_name');
        // Caching (Redis)
        $cacheKey = 'patients:' . md5(json_encode($request->all()));
        $results = Cache::remember($cacheKey, 30, function() use ($query, $count, $offset) {
            return $query->skip($offset)->take($count)->get();
        });
        return response()->json($results);
    }
}
