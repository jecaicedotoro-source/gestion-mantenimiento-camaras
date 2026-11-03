<?php

use App\Exceptions\ClassificationUnavailable;
use App\Infrastructure\AI\OpenAIIncidentClassifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    config(['ai.enabled' => true, 'ai.key' => 'clave-ficticia-exclusiva-de-pruebas', 'ai.model' => 'modelo-configurado-prueba']);
});

function openAIResponse(string $text = '{"category":"imagen","explanation":"La descripción indica pérdida de nitidez."}'): array
{
    return [
        'status' => 'completed',
        'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => $text]]]],
        'usage' => ['input_tokens' => 30, 'output_tokens' => 20],
    ];
}

test('el adaptador utiliza Responses con JSON estricto y almacenamiento deshabilitado', function () {
    Http::fake(['api.openai.com/v1/responses' => Http::response(openAIResponse())]);
    $result = app(OpenAIIncidentClassifier::class)->classify('La imagen se ve borrosa de forma permanente.');

    expect($result->category->value)->toBe('imagen')->and($result->inputTokens)->toBe(30);
    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.openai.com/v1/responses'
            && $request['store'] === false
            && $request['model'] === 'modelo-configurado-prueba'
            && data_get($request->data(), 'text.format.strict') === true
            && data_get($request->data(), 'text.format.type') === 'json_schema'
            && data_get($request->data(), 'text.format.schema.additionalProperties') === false
            && data_get($request->data(), 'input.0.content.0.type') === 'input_text'
            && ! array_key_exists('camera_id', $request->data())
            && ! array_key_exists('user', $request->data());
    });
    Http::assertSentCount(1);
});

test('rechaza categoría inexistente JSON malformado y justificación excesiva', function (string $text) {
    Http::fake(['api.openai.com/v1/responses' => Http::response(openAIResponse($text))]);

    expect(fn () => app(OpenAIIncidentClassifier::class)->classify('La cámara tiene una falla de conexión.'))->toThrow(ClassificationUnavailable::class);
})->with([
    'categoría inexistente' => ['{"category":"inventada","explanation":"Prueba"}'],
    'JSON malformado' => ['esto no es JSON'],
    'campo adicional' => ['{"category":"imagen","explanation":"Prueba","confidence":0.9}'],
    'justificación larga' => [json_encode(['category' => 'imagen', 'explanation' => str_repeat('x', 501)])],
    'justificación vacía' => ['{"category":"imagen","explanation":""}'],
]);

test('maneja errores HTTP sin exponer cuerpos o claves', function (int $status) {
    Http::fake(['api.openai.com/v1/responses' => Http::response(['error' => 'contenido-sensible-del-proveedor'], $status)]);

    try {
        app(OpenAIIncidentClassifier::class)->classify('La cámara tiene una falla de conexión.');
        $this->fail('Debe rechazar el error HTTP.');
    } catch (ClassificationUnavailable $exception) {
        expect($exception->getMessage())->not->toContain('contenido-sensible', 'clave-ficticia')
            ->and($exception->reason)->toBe($status === 429 ? 'provider_rate_limit' : 'http_error');
    }
    Http::assertSentCount(1);
})->with([401, 429, 500]);

test('maneja tiempo de espera sin reintentos automáticos', function () {
    Http::fake(fn () => throw new ConnectionException('dato privado del transporte'));

    expect(fn () => app(OpenAIIncidentClassifier::class)->classify('La cámara tiene una falla de conexión.'))->toThrow(ClassificationUnavailable::class);
});

test('rechaza respuestas incompletas y negativas del proveedor', function (array $response) {
    Http::fake(['api.openai.com/v1/responses' => Http::response($response)]);

    expect(fn () => app(OpenAIIncidentClassifier::class)->classify('La cámara tiene una falla de conexión.'))->toThrow(ClassificationUnavailable::class);
})->with([
    'incompleta' => [['status' => 'incomplete', 'output' => []]],
    'negativa' => [['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'refusal', 'refusal' => 'No disponible']]]]]],
    'sin salida' => [['status' => 'completed', 'output' => []]],
]);

test('sin configuración no realiza llamadas externas', function () {
    config(['ai.key' => '', 'ai.model' => '', 'ai.enabled' => false]);

    expect(fn () => app(OpenAIIncidentClassifier::class)->classify('La cámara tiene una falla de conexión.'))->toThrow(ClassificationUnavailable::class);
    Http::assertNothingSent();
});
