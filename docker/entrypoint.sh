#!/bin/sh
set -e

# Ensure SQLite file exists before migrations run
if [ "$DB_CONNECTION" = "sqlite" ] && [ -n "$DB_DATABASE" ] && [ "$DB_DATABASE" != ":memory:" ] && [ ! -f "$DB_DATABASE" ]; then
    mkdir -p "$(dirname "$DB_DATABASE")"
    touch "$DB_DATABASE"
    chown www-data:www-data "$DB_DATABASE"
fi

if [ ! -f storage/jwt/private_rsa.pem ]; then
    echo "First-time setup detected..."
    php artisan install:sunset
    php artisan deploy
    echo "Setup complete."
fi

exec php-fpm
