FROM node:22-bookworm-slim AS frontend

WORKDIR /build

COPY package.json package-lock.json webpack.mix.js ./
RUN npm ci

COPY resources ./resources
RUN npm run production


FROM serversideup/php:8.4-cli AS vendor

USER root

RUN install-php-extensions bcmath exif gd intl pdo_mysql zip

WORKDIR /app

COPY . .
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader


FROM serversideup/php:8.4-fpm-nginx

USER root

RUN install-php-extensions bcmath exif gd intl pdo_mysql zip

WORKDIR /var/www/html

COPY --from=vendor --chown=www-data:www-data /app ./
COPY --from=frontend --chown=www-data:www-data \
    /build/public/vendor/naxas-restaurantops \
    ./public/vendor/naxas-restaurantops
COPY --chmod=755 docker/entrypoint.d/60-tastyigniter.sh /etc/entrypoint.d/60-tastyigniter.sh

RUN mkdir -p \
        bootstrap/cache \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/igniter \
        storage/logs \
    && chown -R www-data:www-data bootstrap/cache public storage

USER www-data

HEALTHCHECK --interval=15s --timeout=5s --start-period=90s --retries=5 \
    CMD curl --fail --silent --show-error --output /dev/null http://127.0.0.1:8080/ || exit 1
