FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    curl \
    libzip-dev \
    zip \
    unzip \
    sqlite \
    sqlite-dev \
    openssl \
    oniguruma-dev

RUN docker-php-ext-install \
    pdo \
    pdo_sqlite \
    mbstring \
    bcmath \
    zip \
    pcntl \
    opcache

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY . .

RUN composer run-script post-autoload-dump && \
    mkdir -p database \
            storage/framework/views \
            storage/framework/cache \
            storage/framework/sessions \
            storage/framework/testing \
            storage/logs \
            bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache database && \
    chmod -R 775 storage bootstrap/cache database

EXPOSE 9000

CMD ["php-fpm"]
