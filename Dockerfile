# Multi-stage build for optimized Docker images
FROM php:8.2-fpm-alpine as base

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions in one layer
RUN apk add --no-cache \
        postgresql-dev \
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
        fcgi \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

# Configure PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Add PHP-FPM healthcheck script
RUN echo '#!/bin/sh' > /usr/local/bin/php-fpm-healthcheck \
    && echo 'SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1' >> /usr/local/bin/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

# Configure PHP-FPM
RUN echo "pm.status_path = /status" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "ping.path = /ping" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "ping.response = pong" >> /usr/local/etc/php-fpm.d/www.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Development stage
FROM base as development

# Install development tools
RUN apk add --no-cache \
    nodejs \
    npm \
    sqlite-dev \
    && docker-php-ext-install pdo_sqlite

# Set development environment
ENV APP_ENV=local
ENV APP_DEBUG=true

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader

# Copy package.json for npm dependencies
COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

# Copy application files
COPY . .

# Complete composer installation
RUN composer dump-autoload --optimize

# Set permissions
RUN find /var/www/html -type f -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html

# Create directories for file-based token storage
RUN mkdir -p /var/www/html/storage/app/refresh_tokens \
    && chown -R www-data:www-data /var/www/html/storage/app/refresh_tokens

USER www-data

EXPOSE 9000

CMD ["php-fpm"]

# Builder stage for assets
FROM node:18-alpine as assets

WORKDIR /app

# Copy package files
COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then npm ci --only=production; else npm install --only=production; fi

# Copy source files
COPY resources ./resources
COPY vite.config.js ./
COPY tailwind.config.js* ./
COPY postcss.config.js* ./

# Build assets
RUN npm run build

# Production stage
FROM base as production

# Set production environment
ENV APP_ENV=production
ENV APP_DEBUG=false

# Copy composer files
COPY composer.json composer.lock ./

# Install production dependencies
RUN composer install --no-dev --no-scripts --no-autoloader --optimize-autoloader

# Copy application files
COPY . .

# Copy built assets from assets stage
COPY --from=assets /app/public/build ./public/build

# Complete composer installation and optimize
RUN composer dump-autoloader --optimize --classmap-authoritative \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache

# Set permissions (more restrictive)
RUN find /var/www/html -type f -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html

# Create directories for file-based token storage
RUN mkdir -p /var/www/html/storage/app/refresh_tokens \
    && chown -R www-data:www-data /var/www/html/storage/app/refresh_tokens

# Add health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

USER www-data

EXPOSE 9000

CMD ["php-fpm"]