# Use the official PHP image as the base image (Debian-based)
FROM php:8.3-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies for Debian
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libpq-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    g++ \
    make \
    autoconf \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install mbstring exif pcntl bcmath gd pdo_pgsql pdo_mysql zip calendar

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www

# Move constants directory if it exists
RUN if [ -d /var/www/app/constants ]; then mv /var/www/app/constants /var/www/app/Constants; fi

# Adjust ownership and permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Configure Git to consider /var/www as a safe directory
RUN git config --global --add safe.directory /var/www

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Dump autoload
RUN composer dump-autoload

# Create Laravel required directories
RUN mkdir -p /var/www/storage/logs \
    && mkdir -p /var/www/storage/framework/cache \
    && mkdir -p /var/www/storage/framework/sessions \
    && mkdir -p /var/www/storage/framework/views \
    && mkdir -p /var/www/bootstrap/cache

# Create startup script directly in Dockerfile
RUN echo '#!/bin/bash' > /usr/local/bin/start.sh \
    && echo 'set -e' >> /usr/local/bin/start.sh \
    && echo 'echo "Starting Laravel application initialization..."' >> /usr/local/bin/start.sh \
    && echo 'echo "Waiting for database connection..."' >> /usr/local/bin/start.sh \
    && echo 'sleep 5' >> /usr/local/bin/start.sh \
    && echo 'echo "Clearing Laravel caches..."' >> /usr/local/bin/start.sh \
    && echo 'php artisan config:clear || true' >> /usr/local/bin/start.sh \
    && echo 'php artisan cache:clear || true' >> /usr/local/bin/start.sh \
    && echo 'php artisan route:clear || true' >> /usr/local/bin/start.sh \
    && echo 'php artisan view:clear || true' >> /usr/local/bin/start.sh \
    && echo 'echo "Running database migrations..."' >> /usr/local/bin/start.sh \
    && echo 'php artisan migrate --force || echo "Migration failed"' >> /usr/local/bin/start.sh \
    && echo 'echo "Creating Filament user..."' >> /usr/local/bin/start.sh \
    && echo 'php artisan make:filament-user --name="chaqui" --email="josue.chaqui@gmail.com" --password="test123" --roleid="1" || echo "User exists or creation failed"' >> /usr/local/bin/start.sh \
    && echo 'echo "Creating storage link..."' >> /usr/local/bin/start.sh \
    && echo 'php artisan storage:link || echo "Storage link exists"' >> /usr/local/bin/start.sh \
    && echo 'echo "Caching configurations..."' >> /usr/local/bin/start.sh \
    && echo 'php artisan config:cache' >> /usr/local/bin/start.sh \
    && echo 'php artisan route:cache' >> /usr/local/bin/start.sh \
    && echo 'php artisan view:cache' >> /usr/local/bin/start.sh \
    && echo 'echo "Laravel application ready!"' >> /usr/local/bin/start.sh \
    && echo 'echo "Starting PHP-FPM..."' >> /usr/local/bin/start.sh \
    && echo 'php-fpm -D' >> /usr/local/bin/start.sh \
    && echo 'echo "Starting Laravel server on port 8000..."' >> /usr/local/bin/start.sh \
    && echo 'exec php artisan serve --host=0.0.0.0 --port=8000' >> /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

# Expose ports for PHP-FPM and the web server
EXPOSE 9000 8000

# Use the embedded script with bash
CMD ["/bin/bash", "/usr/local/bin/start.sh"]
