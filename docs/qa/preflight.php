<?php

// Verifica el aislamiento antes de que Pest pueda aplicar RefreshDatabase.
// Solo muestra valores esperados o booleanos; nunca imprime la configuración privada.
try {
    $project = $argv[1] ?? dirname(__DIR__, 2);
    require $project.'/vendor/autoload.php';
    $app = require $project.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $checks = [
        'environment_testing' => $app->environment('testing'),
        'connection_sqlite' => config('database.default') === 'sqlite',
        'database_memory' => config('database.connections.sqlite.database') === ':memory:',
        'database_url_empty' => empty(config('database.connections.sqlite.url')),
        'cache_array' => config('cache.default') === 'array',
        'session_array' => config('session.driver') === 'array',
        'queue_sync' => config('queue.default') === 'sync',
        'mail_array' => config('mail.default') === 'array',
        'openai_disabled' => config('ai.enabled') === false,
        'openai_key_empty' => config('ai.key') === '',
        'openai_model_empty' => config('ai.model') === '',
        'config_not_cached' => ! $app->configurationIsCached(),
        'bootstrap_path_expected' => realpath($app->bootstrapPath()) === realpath($project.'/bootstrap'),
    ];
    $safe = ! in_array(false, $checks, true);
    echo json_encode(['safe' => $safe, 'checks' => $checks], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit($safe ? 0 : 10);
} catch (Throwable) {
    // Una excepción de arranque podría incluir una URL o credencial: no se vuelca su traza.
    echo json_encode([
        'safe' => false,
        'error' => 'No se pudo verificar el arranque aislado. No se autoriza ejecutar Pest.',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit(10);
}
