# syntax=docker/dockerfile:1

# Laravel on FrankenPHP. Front-end assets are built with Node 24.22.0.

FROM dunglas/frankenphp:1.13.1-php8.2-trixie AS base

RUN install-php-extensions bcmath exif gd intl opcache pcntl pdo_mysql pdo_pgsql sockets zip

WORKDIR /app

FROM node:24.22.0-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --optimize \
    && php artisan package:discover --ansi

FROM base AS production

ENV SERVER_NAME=:80

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build

RUN mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
