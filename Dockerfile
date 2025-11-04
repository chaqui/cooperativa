# Use the official PHP image as the base image
FROM php:8.3-fpm-alpine

# Set working directory
WORKDIR /var/www

# Install system dependencies for Alpine
RUN apk update && apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    postgresql-dev \
    zip \
    unzip \
    libzip-dev \
    icu-dev \
    g++ \
    make \
    autoconf \
    bash \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl

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

# Create startup script using printf (more reliable than echo chains)
RUN printf '#!/bin/sh\n\
set -e\n\
\n\
echo "Starting Laravel application initialization..."\n\
echo "Waiting for database connection..."\n\
sleep 5\n\
\n\
echo "Clearing Laravel caches..."\n\
php artisan config:clear || true\n\
php artisan cache:clear || true\n\
php artisan route:clear || true\n\
php artisan view:clear || true\n\
\n\
echo "Running database migrations..."\n\
php artisan migrate --force || echo "Migration failed"\n\
\n\
echo "Creating Filament user..."\n\
php artisan make:filament-user --name="chaqui" --email="josue.chaqui@gmail.com" --password="test123" --roleid="1" || echo "User exists or creation failed"\n\
\n\
echo "Creating storage link..."\n\
php artisan storage:link || echo "Storage link exists"\n\
\n\
echo "Caching configurations..."\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
\n\
echo "Laravel application ready!"\n\
echo "Starting PHP-FPM..."\n\
php-fpm -D\n\
\n\
echo "Starting Laravel server on port 8000..."\n\
exec php artisan serve --host=0.0.0.0 --port=8000\n' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh \
    && ls -la /usr/local/bin/start.sh \
    && head -5 /usr/local/bin/start.sh

# Expose ports for PHP-FPM and the web server
EXPOSE 9000 8000

# Use the embedded script with sh
CMD ["/bin/sh", "/usr/local/bin/start.sh"]
