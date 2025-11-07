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
        // hola
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
