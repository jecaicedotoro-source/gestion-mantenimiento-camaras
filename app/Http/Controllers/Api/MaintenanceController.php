<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MaintenanceRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    /**
     * HU-09: Registrar un mantenimiento de cámara.
     *
     * La autorización se aplica mediante el middleware
     * can:create-maintenances definido en routes/api.php.
     */
    public function store(
        Request $request,
        MaintenanceRegistrationService $service
    ): JsonResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        $maintenance = $service->register($request->all(), $actor);

        return response()->json([
            'message' => 'Mantenimiento registrado correctamente.',
            'data' => $maintenance,
        ], 201);
    }
}
