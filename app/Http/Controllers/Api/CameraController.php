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
        $cameras = Camera::query()
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json($cameras);
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
