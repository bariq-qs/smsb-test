FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        bcmath \
        gd \
        zip \
        intl \
        exif \
        xml \
        dom \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
# Includes dev deps (e.g. fakerphp/faker) since the demo seeders run inside this image.
RUN composer install --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/{cache,sessions,views} storage/app/public storage/logs bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
# `php artisan serve` (Laravel's ServeCommand wrapper) intermittently fails to reach the DB
# from inside this container's network namespace, so we run the built-in server directly.
# `server.php` (a copy of Laravel's own serve-command router) is required rather than
# `public/index.php` directly: passing index.php as the router runs the full framework for
# every request, including static files under public/storage (PDF attachments, Excel
# exports), which don't match any route and come back as 404 HTML instead of the file.
CMD ["sh", "-c", "cd public && exec php -S 0.0.0.0:8000 ../server.php"]
