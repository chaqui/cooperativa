#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

# Run database migrations
php artisan migrate

# Start PHP-FPM
php-fpm
