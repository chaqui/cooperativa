#!/bin/sh
set -e

echo "Starting Laravel application initialization..."

# Wait for database to be ready
echo "Waiting for database connection..."
sleep 5

# Clear any existing caches
echo "Clearing Laravel caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Create Filament user (only if it doesn't exist)
echo "Creating Filament user..."
php artisan make:filament-user \
    --name="${ADMIN_NAME:-chaqui}" \
    --email="${ADMIN_EMAIL:-josue.chaqui@gmail.com}" \
    --password="${ADMIN_PASSWORD:-test123}" \
    --roleid="${ADMIN_ROLE:-1}" || echo "User already exists or failed to create"

# Create the storage symbolic link
echo "Creating storage link..."
php artisan storage:link || echo "Storage link already exists"

# Cache configurations for production
echo "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Laravel application ready!"

# Start PHP-FPM in background
echo "Starting PHP-FPM..."
php-fpm -D

# Start Laravel artisan serve
echo "Starting Laravel server on port 8000..."
exec php artisan serve --host=0.0.0.0 --port=8000
