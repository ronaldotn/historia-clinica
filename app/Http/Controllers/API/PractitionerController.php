<?php

namespace App\Http\Controllers\API;

use App\Models\Practitioner;
use App\Models\AuditEvents;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\StorePractitionerRequest;
use App\Http\Requests\UpdatePractitionerRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PractitionerController extends BaseController
{
    /**
     * Listar todos los profesionales.
     * GET /api/practitioners
     */
    public function index(Request $request): JsonResponse
    {
        $query = Practitioner::query();

        // Filtro por nombre o apellido
        if ($name = $request->input('name')) {
            $query->where(function ($q) use ($name) {
                $q->where('first_name', 'LIKE', "%{$name}%")
                  ->orWhere('last_name', 'LIKE', "%{$name}%");
            });
        }

        // Filtro por identificador/colegiado
        if ($identifier = $request->input('identifier')) {
            $query->where('identifier', $identifier); // importante: soporta validación en tiempo real
        }

        // Filtro por especialidad
        if ($specialty = $request->input('specialty')) {
            $query->where('specialty', 'LIKE', "%{$specialty}%");
        }

        // Filtro por estado
        if ($request->filled('active')) {
            // Aseguramos que el valor sea booleano
            $isActive = filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('active', $isActive);
        }

        $practitioners = $query->orderBy('last_name')->paginate($request->input('count', 15));

        return $this->sendResponse($practitioners, 'Lista de profesionales obtenida.');
    }

    /**
     * Crear un nuevo profesional.
     * POST /api/practitioners
     */
    public function store(StorePractitionerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $practitioner = Practitioner::create($validated);

        // 🕵️‍♂️ Auditoría
        AuditEvents::logCreate(
            Auth::id(),
            'Practitioner/' . $practitioner->id,
            [
                'message'    => "Se creó el profesional {$practitioner->full_name}",
                'created_by' => Auth::user()->name ?? 'System',
                'ip'         => $request->ip(),
                'new_data'   => $validated,
            ]
        );

        return $this->sendResponse($practitioner, 'Profesional creado exitosamente.', 201);
    }

    /**
     * Mostrar un profesional específico.
     * GET /api/practitioners/{id}
     */
    public function show(Practitioner $practitioner): JsonResponse
    {
        return $this->sendResponse($practitioner, 'Profesional encontrado.');
    }

    /**
     * Actualizar un profesional existente.
     * PUT/PATCH /api/practitioners/{id}
     */
    public function update(UpdatePractitionerRequest $request, Practitioner $practitioner): JsonResponse
    {
        $validated = $request->validated();
        $originalData = $practitioner->getOriginal();

        $practitioner->update($validated);

        // 🕵️‍♂️ Auditoría
        AuditEvents::logUpdate(
            Auth::id(),
            'Practitioner/' . $practitioner->id,
            [
                'message'    => "Se actualizó el profesional {$practitioner->full_name}",
                'updated_by' => Auth::user()->name ?? 'System',
                'ip'         => $request->ip(),
                'original'   => $originalData,
                'new_data'   => $validated
            ]
        );

        return $this->sendResponse($practitioner, 'Profesional actualizado exitosamente.');
    }

    /**
     * Eliminar un profesional (desactivación lógica).
     * DELETE /api/practitioners/{id}
     */
    public function destroy(Request $request, Practitioner $practitioner): JsonResponse
    {
        // En lugar de eliminar, lo desactivamos para mantener la integridad referencial.
        $practitioner->update(['active' => false]);

        // 🕵️‍♂️ Auditoría
        AuditEvents::logDelete( // O un método logDeactivate si lo prefieres
            Auth::id(),
            'Practitioner/' . $practitioner->id,
            [
                'message'    => "Se desactivó el profesional {$practitioner->full_name}",
                'updated_by' => Auth::user()->name ?? 'System',
                'ip'         => $request->ip(),
            ]
        );

        return $this->sendResponse([], 'Profesional desactivado exitosamente.');
    }
}
