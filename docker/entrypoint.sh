#!/bin/sh
set -eu

# La clave es obligatoria y permanece fuera de la imagen y los registros.
if [ -z "${APP_KEY:-}" ]; then
    echo 'Configura APP_KEY en el archivo local .env antes de iniciar.' >&2
    exit 1
fi
# El contenedor admite exclusivamente SQLite y guarda sus datos en el volumen privado.
if [ "${DB_CONNECTION:-sqlite}" != "sqlite" ] || [ -z "${DB_DATABASE:-}" ]; then
    echo 'Configura DB_CONNECTION=sqlite y una ruta DB_DATABASE persistente.' >&2
    exit 1
fi
umask 0077
mkdir -p storage/app/data storage/app/backups storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
mkdir -p "$(dirname "$DB_DATABASE")"
if [ ! -f "$DB_DATABASE" ]; then
    touch "$DB_DATABASE"
fi
php artisan app:backup-database --no-interaction
php artisan migrate --force --no-interaction
php artisan config:cache
exec "$@"
