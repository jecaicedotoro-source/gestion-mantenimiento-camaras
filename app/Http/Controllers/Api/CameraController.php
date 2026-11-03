<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Services\CameraRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CameraController extends Controller
{
    /**
     * Consulta las cámaras registradas.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $cameras = Camera::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('codigo_inventario', 'like', '%'.$search.'%')
                        ->orWhere('numero_serie', 'like', '%'.$search.'%')
                        ->orWhere('ubicacion', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json($cameras);
    }

    /** Consulta individual para comprobar la persistencia del registro CU-01. */
    public function show(Camera $camera): JsonResponse
    {
        return response()->json(['data' => $camera]);
    }

    /**
     * Registra una cámara de seguridad.
     */
    public function store(
        Request $request,
        CameraRegistrationService $service
    ): JsonResponse {
        $camera = $service->register($request->all());

        return response()->json([
            'message' => 'Cámara registrada correctamente.',
            'data' => $camera,
        ], 201);
    }
}
