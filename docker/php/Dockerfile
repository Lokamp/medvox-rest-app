# ============================================================================
# Medvox — PHP 8.5-FPM image for the REST API
# ============================================================================
FROM php:8.5-fpm

# --- System dependencies + PHP extensions ---
# libpq-dev is required to build pdo_pgsql (PostgreSQL driver).
# Note: OPcache is already compiled into the official php:8.5-fpm image.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        unzip \
        git \
        curl \
    && docker-php-ext-install pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# --- Composer (dependency manager) ---
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# --- Production PHP settings ---
COPY php.ini /usr/local/etc/php/conf.d/zz-medvox.ini

WORKDIR /var/www/backend

EXPOSE 9000

CMD ["php-fpm"]
