# Multi-stage build for development
FROM php:8.2-fpm-alpine as base

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    sqlite-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    zip \
    unzip \
    git \
    curl \
    nodejs \
    npm \
    supervisor

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pdo_sqlite \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache

# Install Redis extension
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Development stage
FROM base as development

# Set development environment
ENV APP_ENV=local
ENV APP_DEBUG=true

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader

# Copy package.json for npm dependencies
COPY package.json package-lock.json ./
RUN npm install

# Copy application files
COPY . .

# Complete composer installation
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create directories for file-based token storage
RUN mkdir -p /var/www/html/storage/app/refresh_tokens \
    && chown -R www-data:www-data /var/www/html/storage/app/refresh_tokens

USER www-data

EXPOSE 9000

CMD ["php-fpm"]

# Production stage
FROM base as production

# Set production environment
ENV APP_ENV=production
ENV APP_DEBUG=false

# Copy composer files
COPY composer.json composer.lock ./

# Install production dependencies
RUN composer install --no-dev --no-scripts --no-autoloader --optimize-autoloader

# Copy package.json
COPY package.json package-lock.json ./
RUN npm ci --only=production

# Copy application files
COPY . .

# Complete composer installation and optimize
RUN composer dump-autoloader --optimize --classmap-authoritative \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Build assets
RUN npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create directories for file-based token storage
RUN mkdir -p /var/www/html/storage/app/refresh_tokens \
    && chown -R www-data:www-data /var/www/html/storage/app/refresh_tokens

USER www-data

EXPOSE 9000

CMD ["php-fpm"]