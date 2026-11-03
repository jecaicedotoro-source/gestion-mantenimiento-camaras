# Las dependencias y los recursos se construyen sin incorporar secretos locales.
FROM php:8.5-cli-bookworm AS php-base
# La imagen oficial ya incluye mbstring, PDO SQLite y SQLite3; PHP 8.5 integra OPcache.
RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev libzip-dev unzip \
    && docker-php-ext-install intl zip \
    && php -r 'foreach (["intl", "mbstring", "pdo_sqlite", "sqlite3", "zip"] as $extension) { if (!extension_loaded($extension)) { exit(1); } }' \
    && rm -rf /var/lib/apt/lists/*
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

FROM php-base AS php-dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-progress

FROM node:22-bookworm-slim AS assets
WORKDIR /build
COPY package.json package-lock.json .npmrc ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
COPY public ./public
# Flux aporta CSS y Blade a Tailwind; se reutilizan las dependencias de producción.
COPY --from=php-dependencies /app/vendor ./vendor
RUN npm run build

FROM php-base AS app
WORKDIR /app
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite DB_DATABASE=/app/storage/app/data/database.sqlite \
    SESSION_DRIVER=database CACHE_STORE=database
COPY . .
COPY --from=php-dependencies /app/vendor ./vendor
COPY --from=assets /build/public/build ./public/build
RUN mkdir -p storage/app/data storage/app/backups storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --no-dev --optimize \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R u=rwX,g=rX,o= storage bootstrap/cache \
    && chmod +x docker/entrypoint.sh
USER www-data
EXPOSE 8000
ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
