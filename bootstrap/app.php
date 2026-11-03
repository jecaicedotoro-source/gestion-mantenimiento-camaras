<?php

use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // También se ejecuta en las solicitudes Livewire y el desafío de segundo factor.
        $middleware->web(append: [EnsureUserIsActive::class]);
        $middleware->alias(['active' => EnsureUserIsActive::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();
            if (! $request->is('api/*') || $status < 400 || $status === 422) {
                return $response;
            }

            // Las excepciones de Laravel incluyen literales ingleses y detalles con debug activo.
            // Este contrato público conserva el estado y las cabeceras, nunca el contenido interno.
            $message = match ($status) {
                401 => 'Debes autenticarte para acceder a este recurso.',
                403 => 'No tienes permiso para realizar esta operación.',
                404 => 'El recurso solicitado no existe.',
                405 => 'El método HTTP utilizado no está permitido.',
                409 => 'La solicitud entra en conflicto con el estado actual del recurso.',
                419 => 'La sesión ha caducado. Inicia sesión nuevamente.',
                429 => 'Has realizado demasiadas solicitudes. Inténtalo de nuevo más tarde.',
                500 => 'Ocurrió un error interno. Inténtalo de nuevo más tarde.',
                503 => 'El servicio no está disponible temporalmente. Inténtalo de nuevo más tarde.',
                default => 'No se pudo completar la solicitud.',
            };
            $safeResponse = response()->json(['message' => $message], $status, $response->headers->all());
            $safeResponse->headers->set('Content-Type', 'application/json');
            $safeResponse->headers->remove('Content-Length');

            return $safeResponse;
        });
    })->create();
