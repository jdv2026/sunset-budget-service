#!/bin/sh
set -e

# Ensure SQLite file exists before migrations run
DB_FILE="${DB_DATABASE:-/var/www/html/storage/database.sqlite}"
if [ "$DB_CONNECTION" = "sqlite" ] && [ "$DB_FILE" != ":memory:" ]; then
    mkdir -p "$(dirname "$DB_FILE")"
    touch "$DB_FILE"
    chown www-data:www-data "$DB_FILE"
    chmod 664 "$DB_FILE"
fi

if [ ! -f storage/jwt/private_rsa.pem ]; then
    echo "First-time setup detected..."
    php artisan deploy
    echo "Setup complete."
fi

exec php-fpm
