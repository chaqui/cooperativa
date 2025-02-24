#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

# Run database migrations
php artisan migrate --force

php artisan make:filament-user --name="chaqui" --email="josue.chaqui@gmail.com" --password="test123" --roleid="1"

# Create the storage symbolic link
php artisan storage:link

# Start PHP-FPM
php-fpm
