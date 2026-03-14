# --- Stage 1: PHP Dependencies (Composer) ---
FROM php:8.4-fpm-alpine AS vendor

# Install system dependencies for PHP extensions
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev

# Install PHP extensions required by Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy only composer files first to leverage Docker layer caching
COPY composer.json composer.lock ./

# Install dependencies (without dev and with autoloader optimization)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist

# --- Stage 2: Frontend Assets (Node) ---
FROM node:20-alpine AS frontend

WORKDIR /var/www/html

# Copy package files first for caching
COPY package.json package-lock.json ./

# Install npm dependencies
RUN npm ci

# Copy the rest of the app for compilation (CSS, JS, Blade)
COPY . .

# Build production assets (Vite/Tailwind)
RUN npm run build

# --- Stage 3: Final Production Image ---
FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

# Install build-time dependencies AND runtime libraries
RUN apk add --no-cache \
    libpng libpng-dev \
    libzip libzip-dev \
    libjpeg-turbo libjpeg-turbo-dev \
    freetype freetype-dev \
    icu-libs icu-dev \
    fcgi \
    autoconf \
    g++ \
    make

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd bcmath opcache \
    && pecl install redis apcu \
    && docker-php-ext-enable redis apcu

# Clean up dev dependencies to keep image small
RUN apk del libpng-dev libzip-dev libjpeg-turbo-dev freetype-dev icu-dev autoconf g++ make

# Production Opcache tuning
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Copy build artifacts
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=frontend /var/www/html/public/build ./public/build
COPY . .

# Critical: Permission fixes for Laravel
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

USER www-data

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Production Healthcheck: Checks if the PHP-FPM socket is responding
HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
    CMD REDIRECT_STATUS=true SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

# Performance Optimization: Warm up the Laravel caches
# We only discover packages and compile views at build time.
# Config and Route caching should happen at runtime to allow .env changes.
RUN php artisan package:discover && \
    php artisan view:cache

CMD ["php-fpm"]
