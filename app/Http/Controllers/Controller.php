<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //
}


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient; // Asumiendo que tu modelo está en App\Models\Patient
use Illuminate\Validation\ValidationException;

class PatientController extends BaseController
{
    // RUTA: POST /patients
    /**
     * Store a newly created resource in storage.
     * (Almacena un nuevo Patient)
     */
    public function store(Request $request)
    {
        // Paso 1: Validar los datos de entrada
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'identifier' => 'required|unique:patients|string|max:50',
                'birthdate' => 'required|date',
                // ... otras reglas de validación ...
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos', 'details' => $e->errors()], 422);
        }
        
        // Paso 2: Crear el Patient
        $patient = Patient::create($validatedData);

        // Paso 3: Devolver la respuesta
        return response()->json($patient, 201); // Código 201 Created
    }

    // RUTA: GET /patients/{uuid}
    /**
     * Display a specific Patient resource using its UUID.
     */
    public function show($uuid)
    {
        // El enrutador de Laravel puede inyectar el modelo si usamos Route Model Binding, 
        // pero dado que usas 'uuid' en la ruta, lo buscamos manualmente.
        $patient = Patient::where('uuid', $uuid)->first();

        if (!$patient) {
            return response()->json(['message' => 'Paciente no encontrado con ese UUID'], 404);
        }

        return response()->json($patient);
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
}