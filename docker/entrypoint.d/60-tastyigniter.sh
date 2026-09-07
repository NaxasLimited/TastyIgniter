#!/bin/sh

set -eu

cd /var/www/html

attempt=0
until php -r '
    try {
        new PDO(
            sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT"), getenv("DB_DATABASE")),
            getenv("DB_USERNAME"),
            getenv("DB_PASSWORD"),
            [PDO::ATTR_TIMEOUT => 3],
        );
    } catch (Throwable $exception) {
        exit(1);
    }
'; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 30 ]; then
        echo "Database did not become ready in time."
        exit 1
    fi

    sleep 2
done

php artisan vendor:publish --tag=laravel-assets --force --no-interaction
php artisan igniter:up --force --no-interaction
php artisan storage:link --force --no-interaction
php artisan optimize
