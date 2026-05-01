FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libsqlite3-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip intl calendar \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first for better caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy package files and install node deps
COPY package.json package-lock.json ./
RUN npm ci

# Copy the rest of the application
COPY . .

# Re-run composer scripts (package discovery etc.)
RUN composer run-script post-autoload-dump

# Build frontend assets
RUN npm run build

# Create storage link & set permissions
RUN php artisan storage:link 2>/dev/null || true
RUN chmod -R 775 storage bootstrap/cache

# Expose port
EXPOSE ${PORT:-8080}

# Start command: cache config at runtime (picks up Railway env vars), then serve
CMD touch .env && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    (php artisan migrate --force --seed && touch storage/installed || echo "Migration/seed failed - check DB config") && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
