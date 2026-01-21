# ==============================================================================
# STAGE 1: Build Dependencies (PHP/Composer)
# ==============================================================================
FROM composer:2 AS build_composer

WORKDIR /app
COPY composer.json composer.lock ./
# Install only production dependencies
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader --ignore-platform-reqs

# ==============================================================================
# STAGE 2: Build Frontend Assets (Node.js)
# ==============================================================================
FROM node:20-bookworm AS build_assets

WORKDIR /app
COPY package.json package-lock.json ./
# Install node dependencies
RUN npm ci

# Copy only file needed for build
COPY assets ./assets
# Build assets (copies libs to assets/vendor)
RUN npm run build

# ==============================================================================
# STAGE 3: Final Production Image
# ==============================================================================
FROM php:8.3-fpm-bookworm AS app_php

# 1. System Dependencies (Runtime only)
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    postgresql-client \
    imagemagick \
    # Libs required for wkhtmltopdf & PHP extensions
    libxrender1 \
    libfontconfig1 \
    libx11-dev \
    libjpeg62-turbo \
    xfonts-75dpi \
    xfonts-base \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    curl \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# 2. PHP Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    intl \
    pdo_pgsql \
    zip \
    opcache \
    gd \
    curl \
    mbstring \
    xml \
    dom \
    simplexml

# 3. WKHTMLTOPDF Installation (Runtime dependency)
RUN curl -L -o wkhtmltox.deb https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-3/wkhtmltox_0.12.6.1-3.bookworm_amd64.deb \
    && apt-get install -y ./wkhtmltox.deb \
    && rm wkhtmltox.deb

# 4. Nginx & Supervisor Config
COPY nginx.conf /etc/nginx/sites-available/default
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 5. Application Setup
WORKDIR /var/www/html

# Copy Source Code (First, so we don't overwrite vendor/assets later if they overlap)
COPY . .

# Copy Vendor from Stage 1 (Overwrites local vendor if present)
COPY --from=build_composer /app/vendor /var/www/html/vendor

# Copy Assets from Stage 2 (Overwrites local assets/vendor if present)
COPY --from=build_assets /app/assets /var/www/html/assets

# 6. Final PHP/Symfony Setup
ENV APP_ENV=prod

# WKHTMLTOPDF Path
ENV WKHTMLTOPDF_PATH="/usr/local/bin/wkhtmltopdf"

# Limiti per i campi Day/Year
ENV APP_DAY_MIN=1
ENV APP_DAY_MAX=365
ENV APP_YEAR_MIN=0
ENV APP_YEAR_MAX=9999

# Build Arguments: Defaults allow build to pass without flags
# These are overridden by Cloud Run environment variables at runtime
ARG APP_SECRET=build_placeholder_secret
ARG DATABASE_URL=sqlite:///%kernel.project_dir%/var/build.db
ARG GOOGLE_CLIENT_ID=placeholder_client_id
ARG GOOGLE_CLIENT_SECRET=placeholder_client_secret

# Make these available to the RUN commands below (cache:warmup needs them)
ENV APP_SECRET=$APP_SECRET
ENV DATABASE_URL=$DATABASE_URL

# Compila AssetMapper (requires PHP & Vendor)
RUN php bin/console asset-map:compile

# Cache warmup for production
RUN php bin/console cache:warmup --env=prod

# 7. Permissions & User
# Set permissions for www-data (Nginx/PHP-FPM user)
RUN chown -R www-data:www-data /var/www/html/var /var/www/html/public

# Switch to non-root user for security
# Ensure /var/run and other paths are writable if needed by supervisor/nginx
# Nginx typically needs access to /var/log/nginx and /var/lib/nginx
# Supervisor needs access to log and run directories
RUN mkdir -p /var/log/supervisor /var/run/supervisor \
    && chown -R www-data:www-data /var/log/supervisor /var/run/supervisor /var/log/nginx /var/lib/nginx /var/www/html \
    # Create the pid file and give ownership to www-data so nginx can write to it
    && touch /run/nginx.pid \
    && chown www-data:www-data /run/nginx.pid

# 8. Entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
# Ensure entrypoint is owned by www-data or readable/executable
# Since we switch USER, entrypoint runs as www-data

EXPOSE 8080

# Switch User
USER www-data

CMD ["/usr/local/bin/docker-entrypoint.sh"]
