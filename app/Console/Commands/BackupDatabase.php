<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/** Respaldo consistente de SQLite mediante su API, sin copiar una base abierta a medias. */
final class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database';

    protected $description = 'Respalda SQLite antes de aplicar migraciones, sin mostrar datos ni secretos.';

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->error('Este procedimiento de respaldo está preparado para SQLite.');

            return self::FAILURE;
        }

        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);
        $destination = $directory.'/database-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sqlite';
        DB::statement('VACUUM INTO '.DB::connection()->getPdo()->quote($destination));
        $this->info('Respaldo consistente creado en storage/app/backups. Conserva esta carpeta de forma privada.');

        return self::SUCCESS;
    }
}
