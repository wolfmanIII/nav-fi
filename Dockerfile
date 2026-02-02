# ==============================================================================
# FASE 1: Dipendenze di Build (PHP/Composer)
# ==============================================================================
FROM composer:2 AS build_composer

WORKDIR /app
COPY composer.json composer.lock ./
# Installa solo le dipendenze di produzione
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader --ignore-platform-reqs

# ==============================================================================
# FASE 2: Immagine Finale di Produzione (PHP/Nginx)
# ==============================================================================
FROM php:8.3-fpm-bookworm AS app_php

# 1. Dipendenze di Sistema (Solo Runtime)
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    postgresql-client \
    imagemagick \
    # Librerie richieste per wkhtmltopdf & estensioni PHP
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

# 2. Estensioni PHP
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

# 3. Installazione WKHTMLTOPDF (Dipendenza Runtime)
RUN curl -L -o wkhtmltox.deb https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-3/wkhtmltox_0.12.6.1-3.bookworm_amd64.deb \
    && apt-get install -y ./wkhtmltox.deb \
    && rm wkhtmltox.deb

# 4. Configurazione Nginx & Supervisor
COPY nginx.conf /etc/nginx/sites-available/default
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 5. Configurazione Applicazione
WORKDIR /var/www/html

# Copia Codice Sorgente
COPY . .

# Copia Vendor dalla Fase 1 (Sovrascrive vendor locale se presente)
COPY --from=build_composer /app/vendor /var/www/html/vendor

# 6. Setup Finale PHP/Symfony
ENV APP_ENV=prod

# Percorso WKHTMLTOPDF
ENV WKHTMLTOPDF_PATH="/usr/local/bin/wkhtmltopdf"

# Limiti per i campi Day/Year
ENV APP_DAY_MIN=1
ENV APP_DAY_MAX=365
ENV APP_YEAR_MIN=0
ENV APP_YEAR_MAX=9999

# Argomenti di Build
ARG APP_SECRET=build_placeholder_secret
ARG DATABASE_URL=sqlite:///%kernel.project_dir%/var/build.db
ARG GOOGLE_CLIENT_ID=placeholder_client_id
ARG GOOGLE_CLIENT_SECRET=placeholder_client_secret

# Rende questi disponibili ai comandi RUN sottostanti
ENV APP_SECRET=$APP_SECRET
ENV DATABASE_URL=$DATABASE_URL

# Scarica asset vendor (highlight.js, tom-select, etc. in assets/vendor)
RUN php bin/console importmap:install

# Compila CSS Tailwind (richiede symfonycasts/tailwind-bundle)
RUN php bin/console tailwind:build --minify

# Compila AssetMapper
RUN php bin/console asset-map:compile

# Cache warmup per la produzione
RUN php bin/console cache:warmup --env=prod

# 7. Permessi & Utente
# Imposta permessi per www-data (utente Nginx/PHP-FPM)
RUN chown -R www-data:www-data /var/www/html/var /var/www/html/public

# Passa a utente non-root per sicurezza
# Assicura che /var/run e altri percorsi siano scrivibili se necessari a supervisor/nginx
# Nginx tipicamente necessita accesso a /var/log/nginx e /var/lib/nginx
# Supervisor necessita accesso alle directory di log e run
RUN mkdir -p /var/log/supervisor /var/run/supervisor \
    && chown -R www-data:www-data /var/log/supervisor /var/run/supervisor /var/log/nginx /var/lib/nginx /var/www/html \
    # Crea il file pid e dà la proprietà a www-data così nginx può scriverci
    && touch /run/nginx.pid \
    && chown www-data:www-data /run/nginx.pid

# 8. Entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
# Assicura che l'entrypoint sia di proprietà di www-data o leggibile/eseguibile
# Dato che cambiamo USER, l'entrypoint gira come www-data

EXPOSE 8080

# Cambio Utente
USER www-data

CMD ["/usr/local/bin/docker-entrypoint.sh"]
