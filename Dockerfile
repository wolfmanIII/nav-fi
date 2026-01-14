# ==============================================================================
# STAGE 1: Base Image & Dependencies
# ==============================================================================
FROM php:8.3-fpm-bookworm

# Impostiamo la shell su bash per far funzionare NVM
SHELL ["/bin/bash", "-c"]

# 1. Installazione dipendenze di sistema, Nginx, Supervisor e librerie per PDF
RUN apt-get update && apt-get install -y \
    curl \
    gnupg \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    nginx \
    supervisor \
    # Dipendenze grafiche per wkhtmltopdf
    libxrender1 \
    libfontconfig1 \
    libx11-dev \
    libjpeg62-turbo \
    xfonts-75dpi \
    xfonts-base \
    wget \
    # Pulizia cache apt per ridurre dimensioni immagine
    && rm -rf /var/lib/apt/lists/*

# ==============================================================================
# STAGE 2: Node.js (via NVM) & Wkhtmltopdf
# ==============================================================================

# 2. Installazione NVM e Node.js LTS
ENV NVM_DIR=/usr/local/nvm
RUN mkdir -p $NVM_DIR

# Scarica e installa NVM
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash

# Installa Node LTS e configura i PATH globali
# Nota: "source" serve per caricare nvm in questo singolo layer
RUN source $NVM_DIR/nvm.sh \
    && nvm install --lts \
    && nvm alias default lts/* \
    && nvm use default

# Aggiunge Node e NPM al PATH in modo permanente per tutti i processi successivi
ENV NODE_PATH=$NVM_DIR/versions/node/v*/lib/node_modules
ENV PATH=$NVM_DIR/versions/node/v*/bin:$PATH

# 3. Installazione Wkhtmltopdf (Debian Bookworm)
RUN wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-2/wkhtmltox_0.12.6.1-2.bookworm_amd64.deb \
    && apt-get install -y ./wkhtmltox_0.12.6.1-2.bookworm_amd64.deb \
    && rm wkhtmltox_0.12.6.1-2.bookworm_amd64.deb

# ==============================================================================
# STAGE 3: PHP Extensions & Configuration
# ==============================================================================

# 4. Estensioni PHP per Symfony e Postgres
RUN docker-php-ext-install intl pdo_pgsql zip opcache

# 5. Copia configurazioni Nginx e Supervisor
# Assicurati che questi file esistano nella root del progetto!
COPY nginx.conf /etc/nginx/sites-available/default
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ==============================================================================
# STAGE 4: Application Setup & Build
# ==============================================================================

WORKDIR /var/www/html

# 6. Copia codice sorgente
COPY . .

# 7. Installazione dipendenze PHP (Composer)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# 8. Build Frontend (Node.js)
# Questo esegue "npm install" e poi il tuo script "build" nel package.json
# che copierà tom-select e highlight.js nelle cartelle assets/vendor
RUN npm install
RUN npm run build

# (Opzionale ma raccomandato per Symfony 7.4) Compila AssetMapper
RUN php bin/console asset-map:compile

# Cache warmup per produzione
ENV APP_ENV=prod
RUN php bin/console cache:warmup --env=prod

# 9. Permessi finali (Cruciale per cache, log e assets di Symfony)
RUN chown -R www-data:www-data /var/www/html/var /var/www/html/public

# ==============================================================================
# STAGE 5: Entrypoint
# ==============================================================================

# Espone la porta 8080 (standard per Cloud Run)
EXPOSE 8080

# Avvia Supervisor che gestirà Nginx e PHP-FPM
CMD ["/usr/bin/supervisord"]
