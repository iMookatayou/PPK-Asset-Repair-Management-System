# syntax=docker/dockerfile:1.6
# ------------------------------------------------------------------
# Multi-stage Dockerfile for Asset Repair Management System (Laravel)
# Stages:
#   1. frontend-build (Node)  -> builds Vite assets
#   2. vendor (Composer deps) -> installs PHP dependencies
#   3. production (PHP-FPM)   -> final runtime image
# ------------------------------------------------------------------

ARG NODE_VERSION=20-alpine
ARG PHP_VERSION=8.2

# ---------- Stage 1: Build front-end assets ----------
FROM node:${NODE_VERSION} AS frontend-build
WORKDIR /app
# Copy only manifest/package files first for caching
COPY package.json package-lock.json* pnpm-lock.yaml* yarn.lock* ./
RUN if [ -f package-lock.json ]; then npm ci; \
    elif [ -f pnpm-lock.yaml ]; then npm install -g pnpm && pnpm install; \
    elif [ -f yarn.lock ]; then yarn install --frozen-lockfile; \
    else npm install; fi
COPY resources ./resources
COPY postcss.config.js tailwind.config.js vite.config.js ./
# Build assets (ignore failure if dev-only)
RUN npm run build || echo "[WARN] Vite build skipped (missing dev dependencies?)"

# ---------- Stage 2: Install PHP vendor deps ----------
FROM php:${PHP_VERSION}-fpm-alpine AS vendor
# System deps for required PHP extensions
RUN apk add --no-cache bash git curl icu-libs icu-dev libzip-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev oniguruma-dev mariadb-client \
    $PHPIZE_DEPS
# PHP extensions
RUN docker-php-ext-configure intl && \
    docker-php-ext-install pdo_mysql intl mbstring zip exif gd
# Install and enable phpredis via PECL
RUN pecl install redis \
    && docker-php-ext-enable redis
# Opcache recommended (dynamic dev reload kept enabled)
RUN docker-php-ext-install opcache || true
# Install composer (from official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock* ./
# Install dependencies without running scripts (no artisan yet in this stage)
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts \
    || composer install --no-interaction --prefer-dist --no-scripts

# ---------- Stage 3: Final runtime image ----------
FROM php:${PHP_VERSION}-fpm-alpine AS production
# Repeat system deps needed at runtime (lighter than build stage)
RUN apk add --no-cache bash git curl icu-libs libzip libpng libjpeg-turbo freetype oniguruma mariadb-client
# Copy compiled extensions from vendor stage (already built)
COPY --from=vendor /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=vendor /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d
# Create non-root user
RUN addgroup -g 1000 laravel && adduser -G laravel -u 1000 -D laravel
WORKDIR /var/www/html
# Copy vendor (composer) & application source
COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .
# Copy built asset bundle if exists
COPY --from=frontend-build /app/public/build ./public/build
# Ensure storage permissions
RUN chown -R laravel:laravel storage bootstrap/cache
## Generate package discovery cache (non-fatal if not needed)
RUN php artisan package:discover --ansi || true
USER laravel
ENV APP_ENV=production \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=1 \
    PHP_MEMORY_LIMIT=512M
EXPOSE 9000
CMD ["php-fpm"]

# For local dev using docker-compose, volume mount will override source.
# In production, build with: docker build -t arm-app:latest .
