#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

# Generate the application key
php artisan key:generate --force

# Run database migrations
php artisan migrate --force

php artisan make:filament-user --name="chaqui" --email="josue.chaqui@gmail.com" --password="test123" --roleid="1"

# Start PHP-FPM
php-fpm
