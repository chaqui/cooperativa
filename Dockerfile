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
RUN chown -R www-data:www-data /var/www
RUN chmod -R 775 /var/www

# Configure Git to consider /var/www as a safe directory
RUN git config --global --add safe.directory /var/www

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Dump autoload
RUN composer dump-autoload

# Create Laravel required directories and set permissions
RUN mkdir -p /var/www/storage/logs \
    && mkdir -p /var/www/storage/framework/cache \
    && mkdir -p /var/www/storage/framework/sessions \
    && mkdir -p /var/www/storage/framework/views \
    && mkdir -p /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage \
    && chown -R www-data:www-data /var/www/bootstrap/cache \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Copy entrypoint script with proper line endings
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

# Convert line endings and make executable (in case of Windows line endings)
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && ls -la /usr/local/bin/entrypoint.sh

# Expose ports for PHP-FPM and the web server
EXPOSE 9000 8000

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
