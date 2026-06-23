# ============================================================
# Dockerfile — Laravel 11 (PHP 8.3 FPM Alpine)
# ============================================================
FROM php:8.3-fpm-alpine

# ── System Dependencies ──────────────────────────────────────
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    $PHPIZE_DEPS \
    linux-headers

# ── Timezone WIB ─────────────────────────────────────────────
RUN echo "date.timezone = Asia/Jakarta" > /usr/local/etc/php/conf.d/timezone.ini

# ── PHP Extensions ────────────────────────────────────────────
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd

# ── Composer ─────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Working Directory ────────────────────────────────────────
WORKDIR /var/www/html

# ── Install Dependencies (layer cache optimization) ──────────
# Copy composer files dulu, baru install — supaya layer ini
# tidak di-rebuild setiap kali ada perubahan kode aplikasi
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

# ── Copy Application Code ────────────────────────────────────
COPY . .

# ── Generate Optimized Autoloader ────────────────────────────
RUN composer dump-autoload --optimize

# ── File Permissions ─────────────────────────────────────────
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# PHP-FPM berjalan di port 9000 (internal, diakses via nginx)
EXPOSE 9000

CMD ["php-fpm"]
