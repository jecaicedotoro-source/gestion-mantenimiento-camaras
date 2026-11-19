<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

// VACUUM INTO exige una conexión sin transacción: este caso utiliza un SQLite
// efímero propio, sin RefreshDatabase ni acceso a la base configurada por el usuario.
uses(TestCase::class);

test('respaldo SQLite conserva filas confirmadas y genera archivos distintos', function () {
    $originalStorage = storage_path();
    $originalConnection = config('database.default');
    $temporaryDirectory = storage_path('framework/testing/backup-'.Str::uuid());
    File::ensureDirectoryExists($temporaryDirectory);
    $sourceFile = $temporaryDirectory.'/source.sqlite';
    touch($sourceFile);
    config([
        'database.default' => 'backup_test',
        'database.connections.backup_test' => [
            'driver' => 'sqlite', 'database' => $sourceFile, 'foreign_key_constraints' => true,
        ],
    ]);
    app()->useStoragePath($temporaryDirectory);

    try {
        DB::statement('CREATE TABLE backup_probe (id INTEGER PRIMARY KEY, description TEXT NOT NULL)');
        DB::insert('INSERT INTO backup_probe (description) VALUES (?)', ['Dato ficticio confirmado']);

        $this->artisan('app:backup-database')->assertSuccessful();
        $this->artisan('app:backup-database')->assertSuccessful();

        $backups = glob($temporaryDirectory.'/app/backups/*.sqlite');
        expect($backups)->toHaveCount(2);
        foreach ($backups as $backup) {
            $connection = new PDO('sqlite:'.$backup);
            expect($connection->query('SELECT description FROM backup_probe')->fetchColumn())
                ->toBe('Dato ficticio confirmado');
            $connection = null;
        }
        expect(DB::table('backup_probe')->count())->toBe(1);
    } finally {
        DB::purge('backup_test');
        config(['database.default' => $originalConnection]);
        app()->useStoragePath($originalStorage);
        // Se retira únicamente el directorio efímero creado por esta prueba.
        $resolvedDirectory = realpath($temporaryDirectory);
        $testingDirectory = realpath($originalStorage.'/framework/testing');
        if ($resolvedDirectory === false || $testingDirectory === false
            || ! str_starts_with($resolvedDirectory, $testingDirectory.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('El directorio de prueba no pertenece al espacio temporal esperado.');
        }
        File::deleteDirectory($resolvedDirectory);
    }
});
