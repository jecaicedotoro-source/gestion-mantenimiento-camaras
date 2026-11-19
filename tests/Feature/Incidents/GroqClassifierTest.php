<?php

use App\Contracts\IClasificadorIncidencias;
use App\Exceptions\ClassificationUnavailable;
use App\Infrastructure\AI\GroqIncidentClassifier;
use App\Infrastructure\AI\OpenAIIncidentClassifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();

    config([
        'ai.provider' => 'groq',
        'groq.enabled' => true,
        'groq.key' => 'clave-ficticia-de-prueba',
        'groq.model' => 'openai/gpt-oss-20b',
        'groq.timeout' => 15,
        'groq.max_completion_tokens' => 600,
    ]);
});

function s4GroqResponse(
    string $content = '{"category":"imagen","explanation":"La imagen presenta una falla."}'
): array {
    return [
        'choices' => [
            [
                'finish_reason' => 'stop',
                'message' => [
                    'role' => 'assistant',
                    'content' => $content,
                ],
            ],
        ],
        'usage' => [
            'prompt_tokens' => 31,
            'completion_tokens' => 22,
        ],
    ];
}

test('S4 mantiene OpenAI como proveedor predeterminado', function () {
    config(['ai.provider' => 'openai']);

    expect(app(IClasificadorIncidencias::class))
        ->toBeInstanceOf(OpenAIIncidentClassifier::class);
});

test('S4 selecciona Groq de manera explicita', function () {
    expect(app(IClasificadorIncidencias::class))
        ->toBeInstanceOf(GroqIncidentClassifier::class);
});

test('S4 Groq clasifica una incidencia mediante JSON estricto', function () {
    Http::fake([
        'api.groq.com/openai/v1/chat/completions' => Http::response(s4GroqResponse()),
    ]);

    $result = app(IClasificadorIncidencias::class)
        ->classify('La imagen de la cámara se observa borrosa.');

    expect($result->category->value)->toBe('imagen')
        ->and($result->inputTokens)->toBe(31)
        ->and($result->outputTokens)->toBe(22);

    Http::assertSent(function (Request $request): bool {
        return $request->url() ===
                'https://api.groq.com/openai/v1/chat/completions'
            && $request['model'] === 'openai/gpt-oss-20b'
            && data_get(
                $request->data(),
                'response_format.json_schema.strict'
            ) === true
            && data_get(
                $request->data(),
                'response_format.json_schema.schema.additionalProperties'
            ) === false
            && ! array_key_exists('camera_id', $request->data())
            && ! array_key_exists('user', $request->data());
    });

    Http::assertSentCount(1);
});

test('S4 Groq desactivado no realiza solicitudes', function () {
    config(['groq.enabled' => false]);

    expect(fn () => app(GroqIncidentClassifier::class)
        ->classify('La cámara no transmite imagen correctamente.'))
        ->toThrow(ClassificationUnavailable::class);

    Http::assertNothingSent();
});

test('S4 Groq rechaza entradas fuera de los limites', function (string $text) {
    expect(fn () => app(GroqIncidentClassifier::class)->classify($text))
        ->toThrow(ClassificationUnavailable::class);

    Http::assertNothingSent();
})->with([
    'demasiado corta' => ['corta'],
    'demasiado larga' => [str_repeat('A', 2001)],
]);

test('S4 Groq rechaza errores HTTP sin filtrar respuestas privadas', function (
    int $status
) {
    Http::fake([
        'api.groq.com/*' => Http::response(
            ['error' => 'contenido-privado-del-proveedor'],
            $status
        ),
    ]);

    try {
        app(GroqIncidentClassifier::class)
            ->classify('La cámara presenta una falla de conexión.');
        $this->fail('La solicitud debió ser rechazada.');
    } catch (ClassificationUnavailable $exception) {
        expect($exception->getMessage())
            ->not->toContain('contenido-privado-del-proveedor')
            ->and($exception->reason)
            ->toBe($status === 429 ? 'provider_rate_limit' : 'http_error');
    }
})->with([401, 429, 500]);

test('S4 Groq rechaza JSON y categorias invalidas', function (string $json) {
    Http::fake([
        'api.groq.com/*' => Http::response(s4GroqResponse($json)),
    ]);

    expect(fn () => app(GroqIncidentClassifier::class)
        ->classify('La cámara presenta una falla de conexión.'))
        ->toThrow(ClassificationUnavailable::class);
})->with([
    'JSON roto' => ['esto no es JSON'],
    'categoria desconocida' => [
        '{"category":"inventada","explanation":"Prueba"}',
    ],
    'campo adicional' => [
        '{"category":"imagen","explanation":"Prueba","confidence":0.9}',
    ],
    'justificacion vacia' => [
        '{"category":"imagen","explanation":""}',
    ],
]);

test('S4 Groq rechaza respuestas incompletas', function () {
    $body = s4GroqResponse();
    $body['choices'][0]['finish_reason'] = 'length';

    Http::fake([
        'api.groq.com/*' => Http::response($body),
    ]);

    expect(fn () => app(GroqIncidentClassifier::class)
        ->classify('La cámara presenta una falla de conexión.'))
        ->toThrow(ClassificationUnavailable::class);
});

test('S4 Groq maneja errores de conexion', function () {
    Http::fake(fn () => throw new ConnectionException('fallo simulado'));

    expect(fn () => app(GroqIncidentClassifier::class)
        ->classify('La cámara presenta una falla de conexión.'))
        ->toThrow(ClassificationUnavailable::class);
});

test('S4 rechaza proveedores desconocidos', function () {
    config(['ai.provider' => 'proveedor-inexistente']);

    expect(fn () => app(IClasificadorIncidencias::class))
        ->toThrow(LogicException::class);
});
